# TrustGrade Release Notes

## Version 1.1.1 (2025-08-17)

### New Features
- Added activity-level enable/disable setting for TrustGrade
- Implemented advanced settings section for better UI organization
- Added conditional modal display based on question generation settings

### Improvements
- Updated default settings for better user experience:
  - Instructor questions default: 0
  - Submission-based questions default: 5
  - Auto-generate questions default: 0
- Moved all configuration options (except main enable checkbox) to advanced settings section
- Optimized submission processing modal to only display when questions are being generated
- Set default gateway endpoint to http://trustgrade.cloud/

### Configuration Changes
- TrustGrade settings now collapsed by default under "Show more..." advanced section
- Only "Enable TrustGrade for this assignment" checkbox visible by default
- All question generation, timing, and distribution settings moved to advanced

### Technical Updates
- Enhanced form validation and default value handling
- Improved JavaScript initialization logic for submission processing
- Better integration with Moodle assignment module

---

## Version 1.1.0

### Features
- AI-powered question generation from student submissions
- Instructor question bank integration
- Customizable question distribution and timing
- Comprehensive grading interface with multi-question support
- Real-time submission processing with visual feedback

### Core Functionality
- Gateway client for AI service communication
- Question bank management system
- Quiz settings and configuration
- Disclosure handling for academic integrity
- Event observers for Moodle integration

### Localization
- Full English (en) support
- Full Hebrew (he) support

---

## Version 1.0.0

### Initial Release
- Basic TrustGrade plugin functionality
- Integration with Moodle assignment module
- Question generation capabilities
- Grading interface
- Admin configuration panel

---

## System Requirements

- Moodle 4.1 or higher
- PHP 7.4 or higher
- MySQL/PostgreSQL database

## Installation

See [README.md](README.md) for detailed installation instructions.

## Support

For support and issues, please contact CentricApp LTD.

---

**Powered by CentricApp LTD**
