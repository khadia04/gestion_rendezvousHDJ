document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const icon = document.getElementById("loginThemeIcon");

    // 🔥 AJOUT ICI
    const passwordInput = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (!icon) return;

    /* ===============================
       THEME LOGIN (CLONE DASHBOARD)
    =============================== */
    if (localStorage.getItem("loginTheme") === "dark") {
        body.classList.add("dark-mode");
        icon.classList.replace("bi-sun-fill", "bi-moon-fill");
    }

    window.toggleLoginTheme = function () {
        const isDark = body.classList.toggle("dark-mode");
        localStorage.setItem("loginTheme", isDark ? "dark" : "light");

        if (isDark) {
            icon.classList.replace("bi-sun-fill", "bi-moon-fill");
        } else {
            icon.classList.replace("bi-moon-fill", "bi-sun-fill");
        }
    };

    /* ===============================
       TOGGLE MOT DE PASSE (ŒIL)
    =============================== */
    window.togglePassword = function () {
        if (!passwordInput || !eyeIcon) return;

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeIcon.classList.replace("bi-eye", "bi-eye-slash");
        } else {
            passwordInput.type = "password";
            eyeIcon.classList.replace("bi-eye-slash", "bi-eye");
        }
    };
});
