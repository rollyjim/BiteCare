/* ===============================
   TOGGLE PASSWORD VISIBILITY
================================= */
function toggle(id, el) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        el.innerText = "Hide";
    } else {
        input.type = "password";
        el.innerText = "Show";
    }
}


/* ===============================
   REGISTER ROLE CHANGE
================================= */
function changeRole() {
    const role = document.getElementById("roleSelect").value;
    const d = document.getElementById("dynamicFields");
    d.innerHTML = "";

    /* HEALTH WORKER */
    if (role === "health") {
        d.innerHTML = `
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Stuff name</label>
            <input type="text" name="staff_name" required>

            <label>Admin ID</label>
            <input type="text" name="admin_id" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="hpw" required>
                <span class="toggle-password" onclick="toggle('hpw', this)">Show</span>
            </div>
        `;
    }

    /* USER */
    else if (role === "user") {
        d.innerHTML = `
            <label>Full Name</label>
            <input type="text" name="full_name" required>

            <label>Age</label>
            <input type="number" name="age" required>

            <label>Gender</label>
            <input type="text" name="gender" required>

            <label>Birthday</label>
            <input type="date" name="birthday" required>

            <label>Medical History</label>
            <textarea name="medical_history"></textarea>

            <label>Phone</label>
            <input type="text" name="phone" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Facebook</label>
            <input type="text" name="facebook">

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="upw" required>
                <span class="toggle-password" onclick="toggle('upw', this)">Show</span>
            </div>
        `;
    }

    /* SUPER ADMIN */
    else if (role === "superadmin") {
        d.innerHTML = `
            <label>Full Name</label>
            <input type="text" name="full_name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Admin ID</label>
            <input type="text" name="admin_id" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="spw" required>
                <span class="toggle-password" onclick="toggle('spw', this)">Show</span>
            </div>
        `;
    }
}


/* ===============================
   LOGIN ROLE CHANGE
================================= */
function changeLoginRole() {
    const role = document.getElementById("loginRole").value;
    const container = document.getElementById("loginFields");
    container.innerHTML = "";

    if (role === "user" || role === "health" || role === "superadmin") {
        container.innerHTML = `
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="loginPw" required>
                <span class="toggle-password" onclick="toggle('loginPw', this)">Show</span>
            </div>
        `;
    }
}