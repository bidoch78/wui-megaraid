import { screen } from "../../assets/js/screen.js"

class login extends screen {

    #previouscall = null;

    constructor(app) {
      super("login", app);
    }

    buildHTML(options) {

        this.#previouscall = options;

        var html = `<div class="app-login">
            <div class="login-container container">

                <legend>MegaRaid wUI</legend>

                <div class="mb-3">
                    <label for="login-email" class="form-label">Login</label>
                    <input type="email" class="form-control login-item" id="login-email" aria-describedby="emailHelp">
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label">Password</label>
                    <input type="password" class="form-control login-item" id="login-password">
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-primary btn-login login-item">Log in</button>
                </div>

            </div>
        </div>`;

        const $html = $(html);

        $html.find("input").on("keypress", function(event) {
            if(event.keyCode === 13) 
                $(event.currentTarget).closest(".login-container").find(".btn-login").trigger("click");
        });

        $html.find(".btn-login").on("click", $.proxy(function(event) {
            this.disabledInput(true);

            this.api.connect($.proxy(function(json) {

                this.disabledInput(false);

                if (!json.success) {

                    //error
                }
                else {

                    //Logged in
                    this.app.loadingInProgress(true);

                    //Close login window
                    $("body .app-login").remove();
                    $('#app').show();

                    //Call again intial request
                    this.api.getData(this.#previouscall._previouscall.callback, this.#previouscall._previouscall.options);

                }

            }, this), { 'dispError': false, 'post': { 'login': $(".login-container #login-email").val(), 'password': $(".login-container #login-password").val() } }  );

        }, this));

        $('body').append($html);
        $('#app').hide();

        this.app.loadingInProgress(false);

    }

    disabledInput(state) {

        if (state) 
            $(".login-container .login-item").attr("disabled", "");
        else 
            $(".login-container .login-item").removeAttr("disabled", "");


    }

}

export { login };