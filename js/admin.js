jQuery(document).ready(function($) {
    // Add student to class
    $('#add_student').on('click', function() {
        const studentId = $('#student_id').val();
        const classId = $('#post_ID').val();
        
        if (!studentId) {
            alert('Please select a student');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ace_add_student',
                nonce: aceAjax.nonce,
                student_id: studentId,
                class_id: classId
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to show updated student list
                }
            }
        });
    });

    // Remove student
    $('.remove-student').on('click', function() {
        const studentId = $(this).closest('tr').data('student-id');
        const classId = $('#post_ID').val();
        
        if (confirm('Are you sure you want to remove this student?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ace_remove_student',
                    nonce: aceAjax.nonce,
                    student_id: studentId,
                    class_id: classId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // Take attendance
    $('.take-attendance').on('click', function() {
        const sessionId = $(this).closest('tr').data('session-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'GET',
            data: {
                action: 'ace_get_attendance_form',
                nonce: aceAjax.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Show attendance modal with form
                    $('#attendance_modal')
                        .html(response.data.html)
                        .show();
                }
            }
        });
    });
});