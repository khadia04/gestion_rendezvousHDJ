document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const themeIcon = document.getElementById("themeIcon");

    if (!themeIcon) return;

    // Charger le thème sauvegardé
    const savedTheme = localStorage.getItem("loginTheme");

    if (savedTheme === "dark") {
        body.classList.add("dark-mode");
        themeIcon.classList.replace("bi-moon", "bi-sun-fill");
    }

    // Toggle thème
    window.toggleTheme = function () {
        body.classList.toggle("dark-mode");

        if (body.classList.contains("dark-mode")) {
            localStorage.setItem("loginTheme", "dark");
            themeIcon.classList.replace("bi-moon", "bi-sun-fill");
        } else {
            localStorage.setItem("loginTheme", "light");
            themeIcon.classList.replace("bi-sun-fill", "bi-moon");
        }
    };
});
