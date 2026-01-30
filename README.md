# Prompt to PPT - Moodle Activity Plugin

## Description
This plugin allows Moodle users to generate PowerPoint presentations from text prompts using Google Gemini AI.

## Features
- Generate clean, professional PowerPoint slides from text prompts
- Customize number of slides (1-20)
- AI-powered content generation using Google Gemini
- Download generated presentations directly

## Requirements
- Moodle 4.1 or higher
- PHP 7.4 or higher
- Google Gemini API key (free tier available)

## Installation

### Method 1: Install via ZIP (Recommended)
1. Download the plugin ZIP file
2. Go to **Site administration → Plugins → Install plugins**
3. Upload the ZIP file
4. Click "Install plugin from the ZIP file"
5. Follow the on-screen instructions

### Method 2: Manual Installation
1. Extract the ZIP file
2. Upload the `pptgen` folder to `/mod/` directory on your Moodle server
3. Visit **Site administration → Notifications**
4. Complete the installation process

## Configuration

### Get a Google Gemini API Key
1. Visit https://ai.google.dev/
2. Sign in with your Google account
3. Click "Get API Key" → "Create API Key"
4. Copy your API key

### Add API Key to Moodle
1. Go to **Site administration → Plugins → Local plugins → Prompt to PPT**
2. Paste your Gemini API key in the "Google Gemini API Key" field
3. Click "Save changes"

## Usage

### For Teachers
1. Go to your Moodle course
2. Turn editing on
3. Click "Add an activity or resource"
4. Select "Prompt to PPT"
5. Give it a name and save

### For Students/Users
1. Click on the "Prompt to PPT" activity
2. Enter your presentation topic/prompt
3. Choose number of slides (1-20)
4. Click "Generate PPT"
5. Download your presentation

## Example Prompts
- "Create a presentation about Machine Learning fundamentals"
- "Explain the water cycle for grade 5 students"
- "Benefits of renewable energy sources"

## Troubleshooting

**Error: "Gemini API key not configured"**
- Make sure you've added your API key in plugin settings

**Error: "PPT template not found"**
- Ensure the `template/template.pptx` file exists in the plugin folder

**Blank slides generated**
- Check your Gemini API quota at https://ai.google.dev/
- Verify your prompt is clear and specific

## Support
For issues and feature requests, contact the developer.

## License
GNU GPL v3 or later

## Author
Developed by [Your Name]
Version 0.1 (Proof of Concept)
