import { api } from "../../assets/js/api.js"
import { myEvent, myEventSubscriber } from "../../assets/js/myevent.js"
import { login } from "../../assets/js/login.js"
import { screen } from "../../assets/js/screen.js"
import { overview } from "../../assets/js/overview.js"
import { virtualdrives } from "../../assets/js/virtualdrives.js"
import { physicaldrives } from "../../assets/js/physicaldrives.js"
import { satadrives } from "../../assets/js/satadrives.js"

class app_core {

    #api = null;
    #container = null;
    #loadingInPrgDiv = null;
    #event = new myEvent({ 'adapterId': -1 });
    #screen = null;
    #login = null;

    #appName = "MegaRaid wUI";

    constructor(container) {
        this.#api = new api(this);
        this.#container = container;
        if (!container) {
            console.log("app container not found");
        }
        screen._defaultModalTitle = this.#appName;

        this.#login = new login(this);
        this.#login.container = $("body");

        $("head title").text(this.#appName);
        this.loadingInProgress(true);
        this.buildHTML();
        this.loadData();

        //window.onhashchange = function() {
        //  alert("ok");
        //};

        //window.addEventListener('popstate', function(event) {
        //  this.alert("ok");
        //});

    }

    get api() { return this.#api; }
    get event() { return this.#event; }

    showError(msg) {

      const $container = $('body');
      var $divError = $container.find(".toast-error-container");
      if ($divError.length == 0) {

        $divError = $(`<div aria-live="polite" aria-atomic="true" style="width:100%" class="toast-error-container position-absolute top-0">
                        <div class="toast-container position-absolute top-0 end-0 p-3"></div>
                      </div>`);
        $($container).append($divError);

      }
      
      var newError = `<div class="toast mb-1" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                          <span class="badge bg-danger"><i class="bi bi-bell"></i></span>
                          <strong class="me-auto ms-1">Error</strong>
                          <!--small class="text-muted">2 seconds ago</small-->
                          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">` + msg + `</div>
                      </div>`;
      
      $divError.find(".toast-container").prepend(newError);

      new bootstrap.Toast($divError.find(".toast")[0], { 'delay': 10000, 'autohide': true }).show();

    }

    loadingInProgress(status) {

        if (status) {
            
            if (this.#loadingInPrgDiv) return;

            this.#loadingInPrgDiv = document.createElement("div");
            this.#loadingInPrgDiv.classList.add("loading_data");
            this.#loadingInPrgDiv.innerHTML = `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>`;
            
            document.body.append(this.#loadingInPrgDiv);

        }
        else {

            if (!this.#loadingInPrgDiv) return;
            this.#loadingInPrgDiv.remove();
            this.#loadingInPrgDiv = null;

        }

    }

    buildHTML() {

      var curURLScreen = window.location.hash;
      if (curURLScreen) curURLScreen = curURLScreen.substring(1);
      switch(curURLScreen) {
        case "virtualdrives":
        case "virtualdrives":  
        case "physicaldrives":
        case "cfgforeign":
        case "patrol":
        case "bbu":
        case "satadrivers":
          break;
        default:
          curURLScreen = "overview"
      }

      const app_screen = `<div class="row app_div">

                              <div class="col-md-12 app_container">
                              
                                <nav class="navbar header navbar-expand-lg bg-body-tertiary">
                                  <div class="container-fluid">
                                    <a class="navbar-brand" href="#">MegaRaid wUI</a>
                                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="headerDropDown" aria-controls="headerDropDown" aria-expanded="false" aria-label="Toggle navigation">
                                      <span class="navbar-toggler-icon"></span>
                                    </button>
                                    <div class="collapse navbar-collapse" id="headerDropDown">
        
                                      <ul class="navbar-nav me-auto mb-2 mb-lg-0">                                                
                                        <li class="nav-item">
                                          <select class="form-select form-select-mb combo-adapter" disabled aria-label="choose your adapter">
                                            <option></option>
                                          </select>                              
                                        </li>
                                        <li class="nav-item">
                                          <a class="nav-link disabled ` + (curURLScreen == "overview" ? "active" : "") + `" data-screen="overview" href="#">Overview</a>
                                        </li>
                                        <li class="nav-item">
                                          <a class="nav-link disabled ` + (curURLScreen == "virtualdrives" ? "active" : "") + `" data-screen="virtualdrives" href="#">Virtual Drives</a>
                                        </li>
                                        <li class="nav-item">
                                          <a class="nav-link disabled ` + (curURLScreen == "physicaldrives" ? "active" : "") + `" data-screen="physicaldrives" href="#">Physical Drives</a>
                                        </li>
                                        <li class="nav-item">
                                          <a class="nav-link disabled ` + (curURLScreen == "cfgforeign" ? "active" : "") + `" data-screen="cfgforeign" href="#">Foreign Config</a>
                                        </li>                                        
                                        <li class="nav-item">
                                          <a class="nav-link disabled ` + (curURLScreen == "patrol" ? "active" : "") + `" data-screen="patrol" href="#">Patrol</a>
                                        </li>
                                        <li class="nav-item">
                                          <a class="nav-link disabled ` + (curURLScreen == "bbu" ? "active" : "") + `"" data-screen="bbu" href="#">BBU</a>
                                        </li>
                                        <li style="display:none" class="nav-item">
                                          <a class="nav-link ` + (curURLScreen == "satadrivers" ? "active" : "") + `"" data-screen="satadrivers" href="#">SATA</a>
                                        </li>
                                      </ul>
                                      <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="debug_switch" role="switch">
                                        <label class="form-check-label" for="debug_switch">Debug</label>
                                      </div>

                                    </div>
                                  </div>
                                </nav>

                                <div class="screen_div p-2"></div>

                              </div>

                              <div class="col-md-4 debug_div">
                                <div class="accordion">
                                </div>
                              </div>

                            </div>

                            <nav class="navbar footer fixed-bottom bg-body-tertiary">
                              <div class="col-md-12">
                                <p class="text-center placeholder-glow">
                                  <span class="placeholder">MegaRaid wUI <span class="appversion">#######</span></span>
                                </p>
                                <p class="text-center placeholder-glow">
                                  <span class="placeholder"><span class="cliname">#########</span> <span class="cliversion">######</span> (<span class="clidate">##</span>)</span>
                                </p>
                              </div>
                            </nav>`;

        $(this.#container).append(app_screen);

        $(this.#container).find(".header #debug_switch").on("change", $.proxy(function(e) {
            const $currentItem = $(e.currentTarget);
            app_core.createCookie("debug_mode", $currentItem.is(":checked"));
            if ($currentItem.is(":checked")) {
              $(this.#container).find(".app_container").removeClass("col-md-12").addClass("col-md-8");
              $(this.#container).find(".debug_div").show();
            }
            else {
              $(this.#container).find(".app_container").removeClass("col-md-8").addClass("col-md-12");
              $(this.#container).find(".debug_div").hide();
            }
            return false;
        }, this));        

        $(this.#container).find(".header .nav-link").on("click", $.proxy(function(e) {
            const $currentItem = $(e.currentTarget);
            $currentItem.closest(".navbar-nav").find(".nav-link").removeClass("active");
            $currentItem.addClass("active");
            this.goToScreen(this.getCurrentScreen());
            return false;
        }, this));

        if (app_core.readCookie("debug_mode") == "true") {
          $(this.#container).find(".header #debug_switch").prop("checked", true).trigger("change");
        }

    }

    showLogin(options) {
      this.#login.buildHTML(options);
    }

    loadData() {

      this.#api.getVersion($.proxy(function(json) {

        const $footer = $(this.#container).find(".footer");

        $footer.find(".appversion").html(json.api);
        $footer.find(".cliname").html(json.cli.name);
        $footer.find(".cliversion").html(json.cli.version);
        $footer.find(".clidate").html(app_core.formatDateToString(json.cli.date));
  
        if (json.options.displaySATA === true) $(this.#container).find('.nav-link[data-screen="satadrivers"]').closest("li").show();

        $footer.find(".placeholder").removeClass("placeholder");        

        this.#api.getAdapters($.proxy(function(json) { this.updateAdapterList(json); this.goToScreen(this.getCurrentScreen());  }, this));;

      }, this));

    }

    loadAdapters() {

      this.#api.getVersion($.proxy(function(json) {

        const $footer = $(this.#container).find(".footer");

        $footer.find(".appversion").html(json.api);
        $footer.find(".cliname").html(json.cli.name);
        $footer.find(".cliversion").html(json.cli.version);
        $footer.find(".clidate").html(app_core.formatDateToString(json.cli.date));
  
        if (json.options.displaySATA === true) $(this.#container).find('.nav-link[data-screen="satadrivers"]').closest("li").show();

        $footer.find(".placeholder").removeClass("placeholder");        

      }, this));

    }

    addTraces(json) {

      if (json && json.traces) {

        const $debugDiv = $(this.#container).find(".debug_div");
        var countTraces = $debugDiv.find(".trace-item").length;

        for(var i = 0; i < json.traces.length; i++) {

          countTraces++;
          var htmlValue = json.traces[i].return.replaceAll("\n", "<br>") + "<br>";

          var $html = $(`<div class="accordion-item trace-item">
            <h2 class="accordion-header" id="accordeonItemH${countTraces}">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accordeonItem${countTraces}" aria-expanded="true" aria-controls="accordeonItem${countTraces}">
                <i class="bi bi-arrows-expand"></i> ${json.traces[i].cmd}
              </button>
            </h2>
            <div id="accordeonItem${countTraces}" class="accordion-collapse collapse" aria-labelledby="accordeonItemH${countTraces}">
              <div class="accordion-body">
                ${htmlValue}
              </div>
            </div>
          </div>`);

          $html.find(".accordion-button").on("click", function() {
            var $this = $(this);
            if ($this.attr("aria-expanded") == "true") {
              $this.find(".bi").removeClass("bi-arrows-expand").addClass("bi-arrows-collapse");
            }
            else {
              $this.find(".bi").removeClass("bi-arrows-collapse").addClass("bi-arrows-expand");
            }
          });

          $debugDiv.append($html);

        }
      }

    }

    updateAdapterList(json) {

      const $header = $(this.#container).find(".header");
      const $combo = $header.find(".combo-adapter");

      const currentAdapter = $combo.val();
      let currentHasBBU = false;

      let html = "";
        for(var iadapter = 0; iadapter < json.adapters.length; iadapter++) {
        const adapterId = json.adapters[iadapter].adapter_id;
        const adapterName = 'Adapter ' + adapterId + ' - ' + json.adapters[iadapter].product_name;
        const isSelected = ((!currentAdapter && !iadapter) || (currentAdapter == adapterId));
        //const hasBBU = json.adapters[iadapter].supported_adapter_operations.bbu === true;
        const hasBBU = true;
        if (isSelected) currentHasBBU = hasBBU;
        html += '<option data-bbu="' + (hasBBU?1:0) + '"' + (isSelected ? "selected" : "") + ' value="' + adapterId + '">' + adapterName + '</option>';
      }

      $combo.off("change").on("change", $.proxy(function(e) {
        const $this = $(e.currentTarget);
        const eventData = this.#event.value; 
        eventData.adapterId = $this.val();
        this.#event.value = eventData;
        this.hasBBU($this.find('option:selected').attr("data-bbu") == "1");
      }, this));

      $combo.html(html);

      if (currentAdapter != $combo.val()) { 
          const eventData = this.#event.value; 
          eventData.adapterId = $combo.val(); 
          this.#event.value = eventData;
          this.hasBBU(currentHasBBU);
      }

      if (json.adapters.length) {
        $combo.prop("disabled", false);
        $header.find(".nav-link").removeClass("disabled");
      }
      else {
        //No adapter detected
        screen.displayModal({
          'alwaysCloseWithButtons': true,
          'body': "No adapter detected",
          'buttons': [
            { 'caption': 'Ok', 'class': 'btn-outline-primary', closemodal: true, 'visible': true }
          ]
        });
  
      }

    }

    hasBBU(state) {
      const $bbuLink = $(this.#container).find('.header .nav-link[data-screen="bbu"]');
      if (state) {
        $bbuLink.removeClass("disabled");
      }
      else {
        $bbuLink.addClass("disabled");
      }
    }

    getCurrentScreen() {
      return $(this.#container).find(".header .nav-link.active").attr("data-screen");
    }

    goToScreen(screen) {

      if (this.#screen && this.#screen.name == screen) return;

      this.loadingInProgress(true); 
      
      if (this.#screen) { this.#screen.destroy(); this.#screen = null; }

      switch(screen) {
        case "overview": this.#screen = new overview(this); break;        
        case "virtualdrives": this.#screen = new virtualdrives(this); break;
        case "physicaldrives": this.#screen = new physicaldrives(this); break;
        case "satadrivers": this.#screen = new satadrives(this); break;
      }

      window.location.hash = "#" + screen;

      var screenContainer = this.#container.getElementsByClassName("screen_div")[0];

      if (!this.#screen) {
        this.showError("screen `" + screen + "` not found !")
        screenContainer.innerHTML = "";
      }
      else {
        this.#screen.container = screenContainer;
        this.#screen.buildHTML();  
      }

      this.loadingInProgress(false); 

    }

    /******************************** STATIC */

    static formatDateToString(dt) {

      if (dt && dt.date) {
        return dt.date.split(" ")[0];
      }

      return dt;

    }

    static createCookie(name, value, days) {
      if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
      }
      else var expires = "";
      document.cookie = name+"="+value+expires+"; path=/";
    }
  
    static readCookie(name) {
      var nameEQ = name + "=";
      var ca = document.cookie.split(';');
      for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
      }
      return null;
    }
  
    static eraseCookie(name) {
      app_core.createCookie(name,"",-1);
    }
    
}

export { app_core };