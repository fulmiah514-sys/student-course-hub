document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('interest-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var result = document.getElementById('interest-result');
        var payload = {
            programme_id: form.programme_id.value,
            student_name: form.student_name.value,
            email: form.email.value,
            csrf_token: form.csrf_token.value
        };

        result.textContent = 'Submitting…';

        fetch('interest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                result.textContent = data.message;
                result.className = data.success ? 'success' : 'error';
                if (data.success) form.reset();
            })
            .catch(function () {
                result.textContent = 'Network error — please try again.';
                result.className = 'error';
            });
    });
});
