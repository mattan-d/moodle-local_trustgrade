# TrustGrade for Moodle

## Overview

TrustGrade is an AI-powered assessment plugin for Moodle that enhances the grading experience by automatically generating personalized quiz questions based on student submissions. The plugin helps instructors create fair, comprehensive assessments while saving time and ensuring academic integrity.

**⚠️ IMPORTANT: This plugin requires a subscription to the TrustGrade AI Gateway service, which is a paid third-party service operated by CentricApp LTD.**

**Powered by [CentricApp LTD](https://centricapp.co)**

---

## Third-Party Service Requirement

### TrustGrade AI Gateway Service

This plugin **requires** access to the **TrustGrade AI Gateway**, a cloud-based AI service that:
- Generates personalized quiz questions using artificial intelligence
- Processes student submissions to create contextual assessments
- Provides question generation and management APIs

**Service Provider:** CentricApp LTD  
**Website:** https://centricapp.co  
**Support Email:** support@centricapp.co.il

### Obtaining Credentials

To use this plugin, you must:

1. **Contact CentricApp LTD** to request access to the TrustGrade AI Gateway service
   - Email: support@centricapp.co.il
   - Website: https://centricapp.co

2. **Subscribe to a service plan** that meets your institution's needs
   - Pricing and plans vary based on usage and number of users
   - Contact CentricApp LTD for detailed pricing information

3. **Receive your credentials** after subscription approval:
   - **Gateway Endpoint URL** - The API endpoint for your region/instance
   - **API Key** - Your unique authentication key for API access

4. **Configure the plugin** with your credentials (see Configuration section below)

### Data Privacy Notice

By using this plugin, student submissions and assessment data will be transmitted to the TrustGrade AI Gateway service operated by CentricApp LTD. Please ensure this complies with your institution's data privacy policies and applicable regulations (GDPR, FERPA, etc.).

For detailed information about data handling, privacy policies, and terms of service, contact CentricApp LTD at support@centricapp.co.il.

---

## Features

- **AI-Generated Questions**: Automatically create quiz questions from student submissions
- **Instructor Question Bank**: Use questions from your own custom question bank
- **Flexible Question Distribution**: Configure the number of questions from different sources
- **Smart Grading Interface**: Streamlined grading workflow with navigation and disclosure features
- **Advanced Settings**: Control question generation timing, distribution, and behavior
- **Multi-language Support**: Available in English and Hebrew
- **GDPR Compliance**: Full Privacy API implementation for data export and deletion

---

## Requirements

- **Moodle:** 4.1 or higher
- **PHP:** 7.4 or higher
- **Database:** MySQL 5.7+ or PostgreSQL 9.6+
- **TrustGrade AI Gateway subscription** (required - see above)
- **Internet connectivity** for API communication with TrustGrade services

---

## Installation

### Method 1: Via Moodle Plugin Directory (Recommended)

1. Log in to your Moodle site as an administrator
2. Navigate to **Site administration → Plugins → Install plugins**
3. Search for "TrustGrade" or upload the plugin ZIP file
4. Click **Install plugin from the Moodle plugins directory**
5. Review the plugin validation report
6. Click **Continue** and follow the installation prompts
7. Click **Upgrade Moodle database now**

### Method 2: Manual Installation

1. Download the plugin ZIP file from the Moodle Plugins Directory
2. Extract the archive
3. Upload the `trustgrade` folder to your Moodle installation directory:
   \`\`\`
   /path/to/moodle/local/trustgrade/
   \`\`\`
4. Ensure proper file permissions:
   \`\`\`bash
   chown -R www-data:www-data /path/to/moodle/local/trustgrade/
   chmod -R 755 /path/to/moodle/local/trustgrade/
   \`\`\`
5. Log in as an administrator
6. Navigate to **Site administration → Notifications**
7. Follow the installation prompts to complete the database setup

### Post-Installation

After installation, you must configure your API credentials before the plugin will function (see Configuration section below).

---

## Configuration

### Step 1: Global Plugin Settings

1. Log in as a Moodle administrator
2. Navigate to **Site administration → Plugins → Local plugins → TrustGrade**
3. Configure the following required settings:

#### Gateway Endpoint
- **Field name:** Gateway Endpoint
- **Description:** The API endpoint URL for the TrustGrade AI Gateway service
- **Default:** `http://trustgrade.cloud/`
- **What to enter:** The Gateway Endpoint URL provided by CentricApp LTD when you subscribed to the service
- **Example:** `https://api.trustgrade.cloud/v1/` or your institution-specific endpoint

#### API Key
- **Field name:** API Key
- **Description:** Your unique authentication key for accessing the TrustGrade API
- **What to enter:** The API Key provided by CentricApp LTD
- **Security note:** Keep this key confidential and do not share it publicly
- **Example:** `tg_abc123def456ghi789jkl012mno345pqr`

#### Cache Management (Optional)
- Clear the TrustGrade cache if experiencing issues
- Use the "Clear All Caches" button to reset cached data

4. Click **Save changes**

### Step 2: Test Your Configuration

1. After saving your settings, navigate to:
   **Site administration → Plugins → Local plugins → TrustGrade → Test Gateway Connection**
2. Click the **Test Connection** button
3. Verify that the connection is successful
4. If the test fails:
   - Check that your Gateway Endpoint URL is correct
   - Verify your API Key is valid
   - Ensure your server can make outbound HTTPS requests
   - Check firewall settings allow connections to the TrustGrade Gateway
   - Contact support@centricapp.co.il for assistance

### Step 3: Assignment-Level Configuration

When creating or editing an assignment:

1. Scroll to the **TrustGrade** section
2. Check **Enable TrustGrade for this assignment**
3. Click **Show more** to access advanced settings:

#### Questions from Instructor Bank
- **Description:** Number of questions to pull from your custom question bank
- **Default:** 0
- **Range:** 0-20
- **Use case:** Use your pre-written questions alongside AI-generated ones

#### Questions Based on Submissions
- **Description:** Number of AI-generated questions personalized to each student's submission
- **Default:** 5
- **Range:** 0-20
- **Use case:** Generate unique questions that test understanding of submitted work

#### Questions to Create
- **Description:** Number of general questions to generate for the assignment topic
- **Default:** 0
- **Range:** 0-50
- **Use case:** Create a pool of questions for the assignment

#### Question Generation Timing
- **Description:** When the AI should generate questions
- **Options:**
  - **After submission:** Generate immediately when student submits (recommended)
  - **After due date:** Wait until assignment due date has passed
- **Default:** After submission

4. Click **Save and display** or **Save and return to course**

---

## Usage

### For Instructors

#### Setting Up TrustGrade Assignments

1. **Create a new assignment** or edit an existing one
2. **Enable TrustGrade** in the TrustGrade section
3. **Configure question distribution** based on your assessment goals:
   - Use instructor questions for standardized assessment
   - Use AI-generated questions for personalized assessment
   - Mix both for comprehensive evaluation
4. **Save the assignment**

#### Student Workflow

1. Students access the assignment
2. Students complete and submit their work
3. TrustGrade generates personalized quiz questions (if configured)
4. Students receive their quiz questions
5. Students complete the quiz

#### Grading Workflow

1. Navigate to the assignment
2. Click **View all submissions**
3. Click on a student's submission to access the TrustGrade grading interface
4. The grading interface provides:
   - **Navigation controls** to move between students
   - **Question bank viewer** to review available questions
   - **Question editor** to customize or create questions
   - **Disclosure controls** to manage question visibility
   - **Quiz results** to view student quiz performance
5. Grade the submission using Moodle's standard grading tools
6. Use the navigation buttons to move to the next student

#### Managing Questions

**Question Bank:**
- Access via the grading interface
- View all questions for the assignment
- Questions are categorized by source (instructor/AI-generated)

**Question Editor:**
- Edit AI-generated questions to improve clarity
- Add follow-up questions
- Adjust difficulty levels
- Preview questions before students see them

**Disclosure Management:**
- Control when students can see questions
- Manage question visibility per student
- Track disclosure status

### For Students

1. **Submit your assignment** by the due date
2. **Wait for quiz generation** (may take a few moments)
3. **Access your personalized quiz** via the assignment page
4. **Complete the quiz** to demonstrate understanding
5. **View your quiz results** after submission

---

## Question Bank Integration

TrustGrade provides a specialized question bank interface for managing instructor-created questions.

### Accessing the Question Bank

- Navigate to **Site administration → Plugins → Local plugins → TrustGrade → Question Bank**
- Or access it directly from the grading interface

### Creating Questions

1. Click **Create New Question**
2. Enter question text
3. Add answer options (for multiple choice)
4. Set correct answer(s)
5. Add explanation or feedback
6. Assign to categories/assignments
7. Save the question

### Managing Questions

- **Edit:** Modify existing questions
- **Delete:** Remove questions (with confirmation)
- **Preview:** See how questions appear to students
- **Export:** Download questions for backup
- **Import:** Upload questions from other sources

---

## Reports and Analytics

### Quiz Reports

View comprehensive quiz results for TrustGrade assessments:

1. Navigate to the assignment
2. Click **View all submissions**
3. Access **TrustGrade Quiz Report**
4. View aggregate statistics:
   - Average quiz scores
   - Question difficulty analysis
   - Common wrong answers
   - Time spent per question

### Student Performance

Track individual student performance:
- Quiz completion rates
- Question-by-question analysis
- Areas needing improvement
- Progress over time

---

## Troubleshooting

### Common Issues

#### "Connection failed" error
- **Cause:** Cannot connect to TrustGrade Gateway
- **Solution:** 
  - Verify Gateway Endpoint URL is correct
  - Check API Key is valid
  - Ensure firewall allows outbound HTTPS connections
  - Test connection via the Gateway Test page

#### "Invalid API Key" error
- **Cause:** API authentication failed
- **Solution:**
  - Verify API Key is entered correctly (no extra spaces)
  - Check that your subscription is active
  - Contact support@centricapp.co.il to verify your credentials

#### Questions not generating
- **Cause:** Multiple possible causes
- **Solution:**
  - Check that TrustGrade is enabled for the assignment
  - Verify student has submitted their work
  - Check question generation timing setting
  - Review Moodle error logs for details
  - Clear TrustGrade cache via plugin settings

#### Grading interface not loading
- **Cause:** JavaScript or template rendering issue
- **Solution:**
  - Clear browser cache
  - Try a different browser
  - Check JavaScript console for errors
  - Purge Moodle caches: **Site administration → Development → Purge all caches**

### Getting Support

If you encounter issues:

1. **Check the logs:**
   - Navigate to **Site administration → Reports → Logs**
   - Filter by "TrustGrade" component
   - Look for error messages

2. **Test the gateway connection:**
   - Use the built-in Gateway Test page
   - Verify connectivity and credentials

3. **Contact CentricApp LTD Support:**
   - Email: support@centricapp.co.il
   - Include:
     - Your Moodle version
     - Plugin version
     - Error messages or screenshots
     - Steps to reproduce the issue

---

## Privacy and Data Protection

### Privacy API Implementation

TrustGrade fully implements the Moodle Privacy API (GDPR compliance):

- **Data Export:** Users can request export of all their TrustGrade data
- **Data Deletion:** Users can request deletion of their TrustGrade data
- **External Data Transfer:** Clear disclosure that data is sent to TrustGrade AI Gateway

### Data Stored by Plugin

TrustGrade stores the following personal data:

- **User logs:** User actions and timestamps
- **Quiz sessions:** Quiz attempts and completion data
- **Questions:** Generated questions and user responses
- **Debug data:** Error logs and diagnostic information (temporary)

### Data Sent to External Service

The following data is transmitted to the TrustGrade AI Gateway:

- Student submission content
- Assignment requirements and rubrics
- Generated quiz questions and answers
- User IDs (pseudonymized)

**Privacy Policy:** Contact support@centricapp.co.il for detailed privacy policy

### Compliance

This plugin is designed to comply with:
- GDPR (General Data Protection Regulation)
- FERPA (Family Educational Rights and Privacy Act)
- Other international data protection standards

**Note:** Institutions are responsible for ensuring that their use of TrustGrade complies with applicable regulations.

---

## Development

### File Structure

\`\`\`
local/trustgrade/
├── amd/
│   ├── build/           # Compiled JavaScript
│   └── src/             # Source JavaScript modules
├── classes/
│   ├── privacy/         # Privacy API implementation
│   ├── task/            # Scheduled tasks
│   ├── api.php          # External API interface
│   ├── gateway_client.php   # TrustGrade Gateway client
│   ├── grading_manager.php  # Grading workflow
│   ├── observer.php         # Event observers
│   ├── quiz_session.php     # Quiz session management
│   └── ...
├── db/
│   ├── caches.php       # Cache definitions
│   ├── events.php       # Event definitions
│   ├── install.xml      # Database schema
│   └── tasks.php        # Scheduled task definitions
├── lang/
│   ├── en/              # English language strings
│   └── he/              # Hebrew language strings
├── templates/           # Mustache templates
├── cache_management.php # Cache management interface
├── gateway_test.php     # Gateway connection test
├── lib.php              # Core plugin functions
├── question_bank.php    # Question bank interface
├── quiz_interface.php   # Quiz interface
├── quiz_report.php      # Quiz reporting
├── settings.php         # Admin settings
├── styles.css           # Plugin styles
├── quiz_styles.css      # Quiz-specific styles
└── version.php          # Plugin version info
\`\`\`

### Key Components

- **gateway_client.php:** API communication with TrustGrade services using Moodle's curl class
- **quiz_settings.php:** Assignment-level configuration management
- **grading_manager.php:** Grading workflow logic and question distribution
- **observer.php:** Event handling for submissions and grading actions
- **privacy/provider.php:** GDPR compliance and Privacy API implementation

### Database Tables

- `local_trustgrade_logs` - User action logs
- `local_trustgrade_questions` - Generated questions
- `local_trustgrade_quiz_sessions` - Quiz attempt records
- `local_trustgrade_debug` - Temporary debug data

### Scheduled Tasks

- **Cleanup quiz sessions:** Removes old quiz session data (runs daily)

---

## Changelog

### Version 1.1.1 (2025-01-17)

- Added activity-level enable/disable setting
- Implemented Privacy API for GDPR compliance
- Improved cache management with Cache API
- Fixed security issues (capability validation, login checks)
- Updated to use Moodle's curl class for proxy support
- Fixed missing method implementations
- Added comprehensive GPL headers to all files
- Namespaced CSS selectors to prevent conflicts
- Localized all hard-coded strings
- Enhanced documentation

### Version 1.0.0 (Initial Release)

- AI-powered question generation
- Instructor question bank
- Grading interface enhancements
- Multi-language support (English, Hebrew)

---

## License

This plugin is licensed under the GNU General Public License v3.0 or later.

**Copyright:** 2025 CentricApp LTD <support@centricapp.co.il>  
**License:** http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

---

## Credits

**Developed and maintained by:** CentricApp LTD  
**Website:** https://centricapp.co  
**Support:** support@centricapp.co.il

---

## Additional Resources

- **Moodle Plugins Directory:** [TrustGrade Plugin Page](https://moodle.org/plugins/local_trustgrade)
- **CentricApp Website:** https://centricapp.co
- **Support Email:** support@centricapp.co.il
- **Documentation:** See this README file

---

**Questions? Need help?** Contact CentricApp LTD support at support@centricapp.co.il
