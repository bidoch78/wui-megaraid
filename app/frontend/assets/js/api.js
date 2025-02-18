
class api {

    #app = null;
    static #url = 'api';

    constructor(app) {
        this.#app = app;
    }

    generateApiError(json, options) {

        if (!json || json.success === 1) return;
        this.#app.showError(json.status + " - " + json.message);

    }

    async getData(callback, options) {

        const fetchOption = {
            method: "GET",
            headers: {
                'Xsrf-Token': localStorage.getItem("xsrf-token") ?? ""
            },
            credentials: 'include',
            mode: 'cors'
        }

        if (options && options.post) {
            fetchOption.method = "POST";
            fetchOption.body = JSON.stringify(options.post);
        }

        const response = await fetch(options.url, fetchOption);
        if (!response.ok) {
            switch(response.status) {
                case 498:
                    this.#app.showLogin({ '_previouscall': { 'callback': callback, 'options': options } }); break;
                default:
                    if (!(this.options && this.options.dispError === false))
                        this.#app.showError("http error: "  + response.status + " " + response.statusText);
            }
            return;
        }

        const json = response.json();

        json.then($.proxy(function(json, p) {
            if (!(this.options && this.options.dispError === false)) this.ref.generateApiError(json);
            if (json["auth"] && json["auth"]["xsrfToken"]) localStorage.setItem("xsrf-token", json["auth"]["xsrfToken"]);
            this.ref.#app.addTraces(json);
            this.callback(json)
        }, { ref: this, response: response, callback: callback, options: options }))
        .catch($.proxy(function(error) {
            if (!(this.options && this.options.dispError === false)) this.ref.#app.showError("http status code: " + this.response.status);
        }, { ref: this, response: response, options: options }));

    }

    async connect(callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + "/user/login" }, options ));
    }

    async getVersion(callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + "/version" }, options ));
    }

    async getAdapters(callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + "/adapter/all/config" }, options ));
    }

    async getAdapterConfig(adapterId, callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + '/adapter/' + adapterId + '/config' }, options ));
    }

    async getOverview(adapterId, callback, options) {
        return this.getData(callback, $.extend({ url: api.#url + '/adapter/' + adapterId + '/overview' }, options ));
    }

    async getPhysicalDrives(adapterId, callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + '/adapter/' + adapterId + '/physicaldrives' }, options ));        
    }

    async sendPhysicalDrivesCommand(adapterId, callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + '/adapter/' + adapterId + '/physicaldrives/command'}, options ));        
    }

    async getSataDrives(callback, options) {
        return this.getData(callback, $.extend( { url: api.#url + "/sata" } , options));
    }

}

export { api };