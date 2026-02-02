(() => {
    /* =========================
       PASSWORD STRENGTH CHECK
    ========================= */

    const passwordInput   = document.getElementById("password");
    const strengthBar     = document.getElementById("strengthBar");
    const strengthText    = document.getElementById("strengthText");
    const strengthWrapper = document.getElementById("strengthWrapper");
    const submitBtn       = document.getElementById("submitBtn");

    // 🚫 Si la page n’a pas de champ mot de passe → on stop le script
    if (!passwordInput) return;

    let passwordStrong = false;

    passwordInput.addEventListener("input", () => {
        const value = passwordInput.value;

        if (value.length === 0) {
            strengthWrapper.style.display = "none";
            submitBtn.disabled = true;
            passwordStrong = false;
            return;
        }

        strengthWrapper.style.display = "block";

        let strength = 0;
        if (value.length >= 8) strength++;
        if (/[A-Z]/.test(value)) strength++;
        if (/[a-z]/.test(value)) strength++;
        if (/[0-9]/.test(value)) strength++;
        if (/[\W_]/.test(value)) strength++;

        if (strength <= 2) {
            strengthBar.style.width = "33%";
            strengthBar.className = "progress-bar bg-danger";
            strengthText.textContent = "Mot de passe faible";
            passwordStrong = false;
        } 
        else if (strength <= 4) {
            strengthBar.style.width = "66%";
            strengthBar.className = "progress-bar bg-warning";
            strengthText.textContent = "Mot de passe moyen";
            passwordStrong = false;
        } 
        else {
            strengthBar.style.width = "100%";
            strengthBar.className = "progress-bar bg-success";
            strengthText.textContent = "Mot de passe fort";
            passwordStrong = true;
        }

        updateSubmitState();
    });

    function updateSubmitState() {
        submitBtn.disabled = !passwordStrong;
    }

})();
