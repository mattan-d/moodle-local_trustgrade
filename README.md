# TrustGrade for Moodle

## Overview

TrustGrade is an AI-powered assessment plugin for Moodle that enhances the grading experience by automatically generating personalized quiz questions based on student submissions. The plugin helps instructors create fair, comprehensive assessments while saving time and ensuring academic integrity.

**Powered by [CentricApp LTD](https://centricapp.co)**

## Features

- **AI-Generated Questions**: Automatically create quiz questions from student submissions
- **Instructor Question Bank**: Use questions from your own custom question bank
- **Flexible Question Distribution**: Configure the number of questions from different sources
- **Smart Grading Interface**: Streamlined grading workflow with navigation and disclosure features
- **Advanced Settings**: Control question generation timing, distribution, and behavior
- **Multi-language Support**: Available in English and Hebrew

## Requirements

- Moodle 4.1 or higher
- PHP 7.4 or higher
- Access to TrustGrade Gateway API

## Installation

1. Download the plugin files
2. Extract to your Moodle installation: `moodle/local/trustgrade/`
3. Log in as an administrator
4. Navigate to **Site administration → Notifications**
5. Follow the installation prompts
6. Configure the gateway endpoint in **Site administration → Plugins → Local plugins → TrustGrade**

## Configuration

### Global Settings

Navigate to **Site administration → Plugins → Local plugins → TrustGrade** to configure:

- **Gateway Endpoint**: API endpoint for TrustGrade services (default: http://trustgrade.cloud/)
- **API Key**: Your TrustGrade API authentication key

### Assignment Settings

When creating or editing an assignment:

1. Enable **TrustGrade for this assignment** checkbox
2. Click **Show more** to access advanced settings:
   - **Questions from Instructor Bank**: Number of questions from your question bank (default: 0)
   - **Questions Based on Submissions**: Number of AI-generated questions (default: 5)
   - **Questions to Create**: Number of questions to generate for this assignment (default: 0)
   - **Question Generation Timing**: When to create questions (after submission/after due date)

## Usage

### For Instructors

1. Create an assignment with TrustGrade enabled
2. Students submit their work
3. TrustGrade generates personalized quiz questions based on submissions
4. Grade student work using the enhanced grading interface
5. Students complete their personalized quizzes

### Grading Interface

The TrustGrade grading interface provides:
- Navigation between student submissions
- Question bank management
- Question editor for customization
- Disclosure controls for managing question visibility

## Development

### File Structure

\`\`\`
local/trustgrade/
├── amd/src/              # JavaScript modules
├── classes/              # PHP classes
├── lang/                 # Language strings
├── templates/            # Mustache templates
├── lib.php              # Core plugin functions
├── settings.php         # Admin settings
└── version.php          # Plugin version info
\`\`\`

### Key Components

- **gateway_client.php**: API communication with TrustGrade services
- **quiz_settings.php**: Assignment-level configuration management
- **grading_manager.php**: Grading workflow logic
- **observer.php**: Event handling for submissions and grading

## Support

For issues, questions, or feature requests, please contact **CentricApp LTD** support.

## License

This plugin is licensed under the GNU General Public License v3.0.

## Version

Current version: 1.1.1 (2025)

---

**Developed and maintained by CentricApp LTD**
