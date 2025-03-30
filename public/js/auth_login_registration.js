document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("auth-modal");
    const openModalBtn = document.getElementById("open-auth-modal");
    const closeModalBtn = document.getElementById("close-auth-modal");
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");
    const showLoginBtn = document.getElementById("show-login");
    const showRegisterBtn = document.getElementById("show-register");

    openModalBtn.addEventListener("click", function () {
        modal.classList.remove("hidden");
        document.body.style.overflow = "hidden";
    });

    closeModalBtn.addEventListener("click", function () {
        modal.classList.add("hidden");
        document.body.style.overflow = "auto";
    });

    showLoginBtn.addEventListener("click", function () {
        loginForm.classList.remove("hidden");
        registerForm.classList.add("hidden");
        showLoginBtn.classList.add("active");
        showRegisterBtn.classList.remove("active");
    });

    showRegisterBtn.addEventListener("click", function () {
        registerForm.classList.remove("hidden");
        loginForm.classList.add("hidden");
        showRegisterBtn.classList.add("active");
        showLoginBtn.classList.remove("active");
    });

    modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.classList.add("hidden");
            document.body.style.overflow = "auto";
        }
    });
});
