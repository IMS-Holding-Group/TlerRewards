document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('admin-login-form');
    const loginMessage = document.getElementById('login-message');

    checkSession();

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            showMessage('الرجاء إدخال اسم المستخدم وكلمة المرور', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);
        formData.append('password', password);

        try {
            const response = await fetch('../apis/admin_auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage('تم تسجيل الدخول بنجاح', 'success');
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1000);
            } else {
                showMessage(result.message || 'حدث خطأ أثناء تسجيل الدخول', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('حدث خطأ في الاتصال بالخادم', 'error');
        }
    });

    async function checkSession() {
        try {
            const response = await fetch('../apis/admin_auth.php?action=check_session');
            const result = await response.json();

            if (result.success && result.logged_in) {
                window.location.href = 'index.html';
            }
        } catch (error) {
            console.error('Error checking session:', error);
        }
    }

    function showMessage(message, type) {
        loginMessage.textContent = message;
        loginMessage.className = type;
        loginMessage.style.display = 'block';
        setTimeout(() => {
            loginMessage.style.display = 'none';
        }, 5000);
    }
});

