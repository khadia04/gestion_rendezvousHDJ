document.addEventListener("DOMContentLoaded", () => {
    const password = document.getElementById("password");
    const bar = document.getElementById("strengthBar");
    const text = document.getElementById("strengthText");

    password.addEventListener("input", () => {
        const val = password.value;
        let score = 0;

        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        bar.className = "progress-bar";

        if (score <= 1) {
            bar.style.width = "25%";
            bar.classList.add("bg-danger");
            text.textContent = "Faible";
        } else if (score === 2) {
            bar.style.width = "50%";
            bar.classList.add("bg-warning");
            text.textContent = "Moyen";
        } else if (score === 3) {
            bar.style.width = "75%";
            bar.classList.add("bg-info");
            text.textContent = "Bon";
        } else {
            bar.style.width = "100%";
            bar.classList.add("bg-success");
            text.textContent = "Fort";
        }
    });
});
