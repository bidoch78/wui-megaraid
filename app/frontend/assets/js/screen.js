import { myEvent, myEventSubscriber } from "../../assets/js/myevent.js"

class screen {

    #name = null;
    #app = null;
    #container = null;
    #subscriber = null;
    
    constructor(name, app) {
      this.#app = app;   
      this.#name = name;
    }

    attach(callbackevent) {
      this.#subscriber = new myEventSubscriber(callbackevent);
      this.#app.event.subscribe(this.#subscriber);
    }

    eventRaised(v) { /* do nothing */ }

    destroy() {
      this.#app.event.unsubscribe(this.#subscriber);
      this.#app = null;
      this.#container = null;
      this.#subscriber = null;
    }

    get container() { return this.#container; }
    set container(container) { this.#container = container; }

    get eventValue() { return this.#app.event.value; }

    get app() { return this.#app; }
    get api() { return this.#app.api; }
    get name() { return this.#name; }

    buildHTML() {
    }

    /************** STATIC */

    static _defaultModalTitle = "###";

    static displayModal(opt) {
		
      var contentModalHTML = "";      
      
      if (!opt) opt = {};

      const _data = {
        'options': opt,
        'buttons': []
      }

      const title = (opt & opt.title) ? opt.title : screen._defaultModalTitle;

      var contentModalHTML = `<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="app-dialogLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="app-dialogLabel">${title}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
            </div>
          </div>
        </div>
      </div>`;

      const $modal = $(contentModalHTML);

      // MODAL EVENTS
      $modal.on("hidden.bs.modal", $.proxy(function(e) {
        //Destroy html
        $(e.currentTarget).remove();
        if (this.options.onclose) this.options.onclose(_data);
      }, _data));

      if (opt && opt.size) $modal.find(".modal-dialog").addClass(opt.size);

      _data["div"] = $modal;
      _data["close"] = $.proxy(function() {
        this.div.modal("hide");
      }, _data);

      _data["updateHTML"] = function(html) {
        this.div.find(".modal-body").html(html);
      };

      // BUTTONS       
      _data["displayFooterButtons"] = function(buttons) {

        this.div.find(".modal-footer").html("");

        if (!buttons) return;

        for (let btn of buttons) {

          let btnHTML = `<button type="button" class="btn`;
          if (btn.class) btnHTML += ` ` + btn.class;
          btnHTML += '"';
          if (btn.closemodal) btnHTML += ' data-bs-dismiss="modal"';
          btnHTML += `>${btn.caption}</button>`;
          const $btnHTML = $(btnHTML);

          btn.show = $.proxy(function() { this.css("display", "") }, $btnHTML);
          btn.hide = $.proxy(function() { this.css("display", "none") }, $btnHTML);

          if (btn.visible === false) $btnHTML.css("display", "none");

          if(btn.onclick) {
            $btnHTML.on("click", $.proxy(function(btn) {
              btn.onclick(this);
            }, _data , btn ));
          }

          this.div.find(".modal-footer").append($btnHTML);

        }

      };
      
      let buttons = (_data.options && _data.options.buttons) ? _data.options.buttons : [];
      if (!buttons.length) {
        //Default
        buttons.push({ class: 'btn-secondary', caption: 'Ok', closemodal: true });
      }
      _data.displayFooterButtons(buttons);

      $('body').append($modal);
  
      // PARAMETERS
      if (opt && opt.alwaysCloseWithButtons) $modal.find(".modal-header .btn-close").remove();

      if (opt['class']) $modal.addClass(opt['class']);
      
      if (opt && opt.body) {
        if ($.isFunction(opt.body)) {
          $modal.find(".modal-body").html(opt.body(opt));
        }
        else {
          $modal.find(".modal-body").html(opt.body);
        }
      }      

      // DISPLAY MODAL
      $modal.modal('show');
    
    }


}

export { screen };