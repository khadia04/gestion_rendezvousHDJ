document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.querySelector(".theme-toggle-dashboard");
    const icon = document.getElementById("dashboardThemeIcon");
    const body = document.body;

    if (!toggleBtn || !icon) return;

    // Charger le thème sauvegardé
    const savedTheme = localStorage.getItem("dashboardTheme");

    if (savedTheme === "dark") {
        body.classList.add("dark-dashboard");
        icon.classList.replace("bi-sun-fill", "bi-moon-fill");
    } else {
        body.classList.remove("dark-dashboard");
        icon.classList.replace("bi-moon-fill", "bi-sun-fill");
    }

    toggleBtn.addEventListener("click", () => {
        const isDark = body.classList.toggle("dark-dashboard");

        icon.classList.add("rotate");

        if (isDark) {
            icon.classList.replace("bi-sun-fill", "bi-moon-fill");
            localStorage.setItem("dashboardTheme", "dark");
        } else {
            icon.classList.replace("bi-moon-fill", "bi-sun-fill");
            localStorage.setItem("dashboardTheme", "light");
        }

        setTimeout(() => icon.classList.remove("rotate"), 400);
    });
});
