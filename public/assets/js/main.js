/* Login Form */
$(document).ready(function () {
    "use strict";
    $('#loginSubmit').click(function () {

        let email           = $('#loginEmail').val(),
            password        = $('#loginPassword').val()

        if ( (email === "") || (password === "") ) {
            $('#loginAlert').html("<div class='alert alert-danger'>Veuillez completer tous les champs.</div>")
        } else {
            $.ajax({
                type: "POST",
                url: "/login",
                data: {
                    "email": email,
                    "password": password
                },
                dataType: "JSON",
                success: function (data) {
                    $('#loginAlert').html("<div class='alert " + (data.error ? 'alert-danger' : 'alert-success') + "'>" + data.message + (data.error ? " " : "<img style='margin-left: 10px' src='/assets/img/ajax-loader.gif'>") + "</div>")
                    if (!data.error) {
                        setTimeout(() => {
                            if ($_GET('redirect') != null)
                                window.location = $_GET('redirect');
                            else
                                window.location = "https://dev.wogel123.fr";
                        }, 2000)
                    }
                }
            })
        }
    })
})






function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function logout() {
    $.ajax({
        type: "POST",
        url: "/logout",
        dataType: 'JSON',
        data: {"token": getCookie('user_token')},
        success: function (result) {
            window.location = "https://dev.wogel123.fr"
        }
    });
}
function $_GET(param) {
    let vars = {};
    window.location.href.replace(location.hash, '').replace(
        /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
        function (m, key, value) { // callback
            vars[key] = value !== undefined ? value : '';
        }
    );

    if (param) {
        return vars[param] ? vars[param] : null;
    }
    return vars;
}

document.addEventListener("DOMContentLoaded", function(event) {

    function OTPInput() {
        const inputs = document.querySelectorAll('#otp > *[id]');
        for (let i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('keydown', function(event) {
                if (event.key === "Backspace") {
                    inputs[i].value = '';
                    if (i !== 0) inputs[i - 1].focus();
                } else {
                    if (i === inputs.length - 1 && inputs[i].value !== '') {
                        return true;
                    } else if (event.keyCode > 47 && event.keyCode < 58) {
                        inputs[i].value = event.key;
                        if (i !== inputs.length - 1) inputs[i + 1].focus();
                        event.preventDefault();
                    } else if (event.keyCode > 64 && event.keyCode < 91) {
                        inputs[i].value = String.fromCharCode(event.keyCode);
                        if (i !== inputs.length - 1) inputs[i + 1].focus();
                        event.preventDefault();
                    }
                }
            });
        }
    }
    OTPInput();
});
if (!window.location.href.includes('admin')) {
    if (!window.location.href.includes('login')) {
        if (window.location.href.includes('daily-prono') || window.location.href.includes('archive') || window.location.href.includes('prono')) {
            document.getElementById("pronoPage").classList.add("active");
        } else if (window.location.href.includes('contact')) {
            document.getElementById("contactPage").classList.add("active");
        } else if (window.location.href.includes('blog')) {
            document.getElementById("blogPage").classList.add("active");
        } else {
            document.getElementById("homePage").classList.add("active");
        }
    }
}