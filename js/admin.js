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
            beforeSend: function() {
                $('#add_student').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to show updated student list
                } else {
                    alert('Failed to add student. Please try again.');
                }
            },
            error: function() {
                alert('Failed to add student. Please try again.');
            },
            complete: function() {
                $('#add_student').prop('disabled', false);
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
                beforeSend: function() {
                    $('.remove-student').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove student. Please try again.');
                    }
                },
                error: function() {
                    alert('Failed to remove student. Please try again.');
                },
                complete: function() {
                    $('.remove-student').prop('disabled', false);
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
            beforeSend: function() {
                $('.take-attendance').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#attendance_modal')
                        .html(response.data.html)
                        .show();

                    // Handle form submission
                    $('#attendance_form').on('submit', function(e) {
                        e.preventDefault();
                        saveAttendance($(this));
                    });

                    // Handle modal close
                    $('.close-modal').on('click', function() {
                        $('#attendance_modal').hide();
                    });
                } else {
                    alert('Failed to load attendance form. Please try again.');
                }
            },
            error: function() {
                alert('Failed to load attendance form. Please try again.');
            },
            complete: function() {
                $('.take-attendance').prop('disabled', false);
            }
        });
    });

    // Save attendance
    function saveAttendance(form) {
        const sessionId = form.data('session-id');
        const formData = form.serialize();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=ace_save_attendance&session_id=' + sessionId,
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#attendance_modal').hide();
                    // Optional: Show success message or update UI
                } else {
                    alert('Failed to save attendance. Please try again.');
                }
            },
            error: function() {
                alert('Failed to save attendance. Please try again.');
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false);
            }
        });
    }

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).is('#attendance_modal')) {
            $('#attendance_modal').hide();
        }
    });

    // Validate form before post submission
    $('form#post').on('submit', function(e) {
        const teacherId = $('#teacher_id').val();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const classTime = $('#class_time').val();
        const classDays = $('input[name="class_days[]"]:checked').length;

        if (!teacherId || !startDate || !endDate || !classTime || !classDays) {
            e.preventDefault();
            alert('Please fill in all required fields (teacher, dates, time, and at least one class day).');
            return false;
        }

        // Validate date range
        if (new Date(startDate) > new Date(endDate)) {
            e.preventDefault();
            alert('End date must be after start date.');
            return false;
        }
    });
});
