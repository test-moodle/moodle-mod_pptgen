<?php
/**
 * PPT GENERATOR - Generate PowerPoint presentations from text prompts
 * File: ppt_generator.php
 * 
 * This file contains functions to:
 * 1. Call Google Gemini API to generate slide content
 * 2. Parse and structure the content
 * 3. Convert text to PPTX using template manipulation
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * CALL GEMINI API
 */
function pptgen_call_gemini(string $prompt, int $slides): string {
    global $CFG;
    
    $apikey = get_config('local_pptgen', 'geminiapikey');
    if (empty($apikey)) {
        throw new moodle_exception('Gemini API key not configured');
    }
    
    // IMPROVED PROMPT - Request structured, clean output
    $system_prompt = "You are a professional PowerPoint presentation creator. 
Generate exactly {$slides} slides with clean, professional content.

For each slide, use this EXACT format:
---SLIDE START---
[SLIDE TITLE]
[BULLET POINT 1]
[BULLET POINT 2]
[BULLET POINT 3]
[BULLET POINT 4]
---SLIDE END---

Rules:
- Title should be 5-10 words max
- Each bullet should be 10-15 words max
- Use 3-4 bullets per slide
- No speaker notes, no metadata, no instructions
- No explanations before or after the slides
- Only output the slide content in the format above";

    $payload = [
        'contents' => [[
            'parts' => [[
                'text' => $system_prompt . "\n\nUser Request:\n" . $prompt
            ]]
        ]],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2000
        ]
    ];
    
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apikey);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new moodle_exception('Gemini API error: HTTP ' . $http_code);
    }
    
    $json = json_decode($response, true);
    if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        throw new moodle_exception('Invalid Gemini response format');
    }
    
    return $json['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * PARSE GEMINI RESPONSE INTO STRUCTURED SLIDES
 */
function pptgen_parse_slides(string $text): array {
    // Split by ---SLIDE START--- and ---SLIDE END---
    $slides = [];
    $pattern = '/---SLIDE\s+START---(.*?)---SLIDE\s+END---/is';
    
    if (preg_match_all($pattern, $text, $matches)) {
        foreach ($matches[1] as $slide_content) {
            $lines = array_map('trim', explode("\n", trim($slide_content)));
            $lines = array_filter($lines); // Remove empty lines
            $lines = array_values($lines); // Reindex
            
            if (count($lines) > 0) {
                $slide = [
                    'title' => $lines[0] ?? 'Untitled',
                    'bullets' => array_slice($lines, 1) // Rest are bullets
                ];
                $slides[] = $slide;
            }
        }
    }
    
    // Fallback: if no proper format found, split by double newlines
    if (empty($slides)) {
        $parts = preg_split('/\n\n+/', trim($text));
        foreach ($parts as $part) {
            $lines = array_map('trim', explode("\n", $part));
            $lines = array_filter($lines);
            if (!empty($lines)) {
                $slide = [
                    'title' => array_shift($lines) ?? 'Slide',
                    'bullets' => $lines
                ];
                $slides[] = $slide;
            }
        }
    }
    
    return $slides;
}

/**
 * CREATE PPTX FROM STRUCTURED SLIDES
 */
function pptgen_create_ppt_from_slides(array $slides, context $context): string {
    global $CFG;
    
    // Create temp directory
    $tempdir = make_temp_directory('pptgen');
    $pptxpath = $tempdir . '/generated.pptx';
    
    // Copy template
    $template = __DIR__ . '/template/template.pptx';
    if (!file_exists($template)) {
        throw new moodle_exception('PPT template not found at: ' . $template);
    }
    
    copy($template, $pptxpath);
    
    // Open PPTX as ZIP
    $zip = new ZipArchive();
    if ($zip->open($pptxpath) !== true) {
        throw new moodle_exception('Unable to open PPTX file');
    }
    
    // Process each slide
    $slide_index = 1;
    foreach ($slides as $slide) {
        $slidefile = "ppt/slides/slide" . $slide_index . ".xml";
        
        if ($zip->locateName($slidefile) !== false) {
            $xml = $zip->getFromName($slidefile);
            
            // Create clean slide XML
            $xml = pptgen_update_slide_xml($xml, $slide);
            
            $zip->addFromString($slidefile, $xml);
        }
        
        $slide_index++;
    }
    
    $zip->close();
    
    // Save to Moodle File API
    $fs = get_file_storage();
    $filename = 'ppt_' . time() . '.pptx';
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'mod_pptgen',
        'filearea' => 'generated',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename
    ];
    
    // Delete old file if exists
    if ($existing = $fs->get_file($context->id, 'mod_pptgen', 'generated', 0, '/', $filename)) {
        $existing->delete();
    }
    
    $fs->create_file_from_pathname($fileinfo, $pptxpath);
    
    return $pptxpath;
}

/**
 * UPDATE SLIDE XML WITH TITLE AND BULLETS
 * FIXED VERSION - Only replaces text content, preserves XML structure
 */
function pptgen_update_slide_xml(string $xml, array $slide): string {
    $title = htmlspecialchars($slide['title'], ENT_XML1, 'UTF-8');
    $bullets = $slide['bullets'] ?? [];
    
    // Build bullets XML
    $bullets_xml = '';
    foreach ($bullets as $bullet) {
        $bullet_text = htmlspecialchars($bullet, ENT_XML1, 'UTF-8');
        $bullets_xml .= '<a:p><a:pPr lvl="0"/><a:r><a:rPr lang="en-US" dirty="0" smtClean="0"/><a:t>' . $bullet_text . '</a:t></a:r><a:endParaRPr lang="en-US" dirty="0"/></a:p>';
    }
    
    // FIXED: Replace only TEXT content, not XML tags
    // Replace title (first <a:t> tag)
    $xml = preg_replace_callback(
        '/<a:t>([^<]*)<\/a:t>/i',
        function($matches) use ($title, &$title_replaced) {
            if (!isset($title_replaced)) {
                $title_replaced = true;
                return '<a:t>' . $title . '</a:t>';
            }
            return $matches[0];
        },
        $xml,
        1
    );
    
    // FIXED: Replace bullets - find content body and replace ONLY its inner content
    // Look for the second shape (id="3" or second <p:txBody>)
    $xml = preg_replace(
        '/(<p:cNvPr id="3"[^>]*>.*?<p:txBody>).*?(<\/p:txBody><\/p:sp>)/is',
        '$1<a:bodyPr/><a:lstStyle/>' . $bullets_xml . '$2',
        $xml,
        1
    );
    
    return $xml;
}

/**
 * MAIN FUNCTION - Generate PPT from prompt
 */
function pptgen_generate_ppt(string $prompt, int $slides, context $context): string {
    // 1. Call Gemini API
    $text = pptgen_call_gemini($prompt, $slides);
    
    if (empty($text)) {
        throw new moodle_exception('No response from Gemini API');
    }
    
    // 2. Parse into structured slides
    $structured_slides = pptgen_parse_slides($text);
    
    if (empty($structured_slides)) {
        throw new moodle_exception('Could not parse slides from response');
    }
    
    // 3. Limit to requested number
    $structured_slides = array_slice($structured_slides, 0, $slides);
    
    // 4. Create PPTX
    $pptpath = pptgen_create_ppt_from_slides($structured_slides, $context);
    
    return $pptpath;
}
