document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.querySelector(".theme-toggle-dashboard");
    const icon = document.getElementById("dashboardThemeIcon");
    const body = document.body;

    if (!toggleBtn || !icon) return;

    // Charger le thème depuis localStorage
    const savedTheme = localStorage.getItem("dashboardTheme");

    if (savedTheme === "dark") {
        body.classList.add("dark-dashboard");
        icon.classList.replace("bi-sun-fill", "bi-moon-fill");
    }

    // Toggle thème
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

        // Retirer la classe d’animation
        setTimeout(() => icon.classList.remove("rotate"), 500);
    });
    document.querySelectorAll(".table tbody tr").forEach(row => {
    row.addEventListener("click", () => {
        row.classList.add("clicked");

        setTimeout(() => {
            row.classList.remove("clicked");
        }, 350);
    });
});
document.querySelectorAll(".table tbody tr").forEach(row => {
    row.addEventListener("click", () => {
        document
          .querySelectorAll(".table tbody tr")
          .forEach(r => r.classList.remove("selected"));

        row.classList.add("selected");
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const rows = document.querySelectorAll(".table tbody tr");

    rows.forEach(row => {

        // CLICK SUR LIGNE
        row.addEventListener("click", () => {

            // remove previous selected
            rows.forEach(r => r.classList.remove("selected"));

            // add selected
            row.classList.add("selected");

            // flash animation
            row.classList.add("clicked");
            setTimeout(() => row.classList.remove("clicked"), 350);
        });

        // EDIT BUTTON → conserve la ligne active
        const editBtn = row.querySelector(".btn-primary, .btn-warning");
        if (editBtn) {
            editBtn.addEventListener("click", e => {
                e.stopPropagation();
                row.classList.add("selected");
            });
        }

        // DELETE BUTTON → conserve la ligne active
        const deleteBtn = row.querySelector(".btn-danger");
        if (deleteBtn) {
            deleteBtn.addEventListener("click", e => {
                e.stopPropagation();
                row.classList.add("selected");
            });
        }
    });
});

});
