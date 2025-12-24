let seconds = 60;
const btn = document.getElementById("resendBtn");
const timer = document.getElementById("timer");

if (btn && timer) {
    btn.disabled = true;

    const interval = setInterval(() => {
        timer.textContent = `Renvoyer dans ${seconds}s`;
        seconds--;

        if (seconds < 0) {
            clearInterval(interval);
            btn.disabled = false;
            timer.textContent = "";
        }
    }, 1000);
}