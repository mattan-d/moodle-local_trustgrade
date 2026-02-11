// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Add TrustGrade quiz grade column to assignment grading table
 *
 * @module     local_trustgrade/grading_table
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    
    return {
        init: function(cmid) {
            console.log('[TrustGrade] TrustGrade grading table column loaded for cmid:', cmid);
            
            // Load required strings
            var strings = [
                {key: 'pluginname', component: 'local_trustgrade'},
                {key: 'time_taken', component: 'local_trustgrade'}
            ];
            
            Str.get_strings(strings).then(function(results) {
                var pluginNameStr = results[0];
                var timeTakenStr = results[1];
                
                // Wait for the table to load
                $(document).ready(function() {
                    addTrustGradeColumn(cmid, pluginNameStr, timeTakenStr);
                });
            }).catch(Notification.exception);
        }
    };
    
    function addTrustGradeColumn(cmid, pluginNameStr, timeTakenStr) {
        // Find the grading table
        var gradingTable = $('table.flexible.table.table-striped');
        
        if (gradingTable.length === 0) {
            console.log('[TrustGrade] Grading table not found, retrying...');
            // Retry after a short delay
            setTimeout(function() {
                addTrustGradeColumn(cmid, pluginNameStr, timeTakenStr);
            }, 500);
            return;
        }
        
        console.log('[TrustGrade] Grading table found');
        
        // Add header
        var headerRow = gradingTable.find('thead tr');
        if (headerRow.length > 0) {
            // Check if column already exists
            if (headerRow.find('th.trustgrade-quiz-grade').length === 0) {
                headerRow.append('<th class="header c-trustgrade trustgrade-quiz-grade" scope="col">' + pluginNameStr + '</th>');
            }
        }
        
        // Fetch quiz grades for all users in this assignment
        fetchQuizGrades(cmid, gradingTable, timeTakenStr);
    }
    
    function fetchQuizGrades(cmid, gradingTable, timeTakenStr) {
                Ajax.call([{
                    methodname: 'local_trustgrade_get_quiz_grades_for_grading',
                    args: {
                        cmid: cmid
                    }
                }])[0].done(function(response) {
                    console.log('[TrustGrade] Quiz grades response:', response);
                    
                    if (response.success) {
                        var grades = JSON.parse(response.grades);
                        console.log('[TrustGrade] Parsed grades:', grades);
                        
                        // Add grades to each row
                        var bodyRows = gradingTable.find('tbody tr');
                        bodyRows.each(function() {
                            var row = $(this);
                            
                            // Get user ID from the row
                            var userId = getUserIdFromRow(row);
                            
                            if (userId && grades[userId]) {
                                var gradeData = grades[userId];
                                var pct = parseInt(gradeData.percentage, 10) || 0;
                                var scoreColor = pct >= 80 ? '#0d6832' : (pct < 50 ? '#b02a37' : '#0f6cbf');
                                // Format: 10/60 (17%) 3/02/2026 03:43 זמן שנדרש: 39s
                                var gradeHtml = '<div style="white-space: nowrap; text-align: right; direction: rtl;">';
                                // Score: red <50%, green >=80%, blue otherwise
                                gradeHtml += '<div style="font-weight: bold; color: ' + scoreColor + ';">';
                                gradeHtml += gradeData.earned_points + '/' + gradeData.total_points;
                                gradeHtml += ' (' + gradeData.percentage + '%)';
                                gradeHtml += '</div>';
                                
                                // Date and time
                                if (gradeData.completed) {
                                    var date = new Date(gradeData.completed * 1000);
                                    var dateStr = date.getDate() + '/' + 
                                                 ('0' + (date.getMonth() + 1)).slice(-2) + '/' + 
                                                 date.getFullYear() + ' ' +
                                                 ('0' + date.getHours()).slice(-2) + ':' + 
                                                 ('0' + date.getMinutes()).slice(-2);
                                    gradeHtml += '<div style="font-size: 0.85em; color: #666;">';
                                    gradeHtml += dateStr;
                                    gradeHtml += '</div>';
                                }
                                
                                // Duration
                                if (gradeData.duration) {
                                    gradeHtml += '<div style="font-size: 0.75em; color: #999;">';
                                    gradeHtml += timeTakenStr + ': ' + gradeData.duration + 's';
                                    gradeHtml += '</div>';
                                }
                                
                                gradeHtml += '</div>';
                                
                                row.append('<td class="cell c-trustgrade trustgrade-quiz-grade">' + gradeHtml + '</td>');
                            } else if (userId) {
                                row.append('<td class="cell c-trustgrade trustgrade-quiz-grade">-</td>');
                            } else {
                                row.append('<td class="cell c-trustgrade trustgrade-quiz-grade"></td>');
                            }
                        });
                    } else {
                        console.error('[TrustGrade] Failed to fetch quiz grades:', response.error);
                        // Add empty cells
                        var bodyRows = gradingTable.find('tbody tr');
                        bodyRows.each(function() {
                            $(this).append('<td class="cell c-trustgrade trustgrade-quiz-grade">-</td>');
                        });
                    }
                }).fail(function(error) {
                    console.error('[TrustGrade] AJAX error fetching quiz grades:', error);
                    Notification.exception(error);
                    // Add empty cells
                    var bodyRows = gradingTable.find('tbody tr');
                    bodyRows.each(function() {
                        $(this).append('<td class="cell c-trustgrade trustgrade-quiz-grade">-</td>');
                    });
                });
    }
    
    function getUserIdFromRow(row) {
        // Try to find user ID from various possible locations
        
        // Method 1: From user profile link
        var userLink = row.find('a[href*="/user/view.php"]').first();
        if (userLink.length > 0) {
            var href = userLink.attr('href');
            var match = href.match(/id=(\d+)/);
            if (match) {
                return parseInt(match[1]);
            }
        }
        
        // Method 2: From data attribute
        var userId = row.data('userid');
        if (userId) {
            return parseInt(userId);
        }
        
        // Method 3: From hidden input or other elements
        var userInput = row.find('input[name*="userid"]');
        if (userInput.length > 0) {
            return parseInt(userInput.val());
        }
        
        return null;
    }
});
