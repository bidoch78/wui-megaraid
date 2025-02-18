import { screen } from "../../assets/js/screen.js"

class overview extends screen {

    constructor(app) {
      super("overview", app);
      this.attach(this.eventRaised);
    }

    eventRaised(v) {
      
    }

    buildHTML() {
      
      /*const html = `<div>

        <div>On affiche info adaptateur comme les hdds avec alert sur temperature </div>
        <div>On affiche les VDs sans les détails</div>
        <div>On affiche les PDs sans les détails</div>

        <div>Graph des températures cartes + PDs</div>

      </div>`;*/

      const html = '<div class="accordion" id="accordionOverview"></div>';
      $(this.container).html(html);

      // this.api.getOverview(this.eventValue.adapterId).then($.proxy(function(json) { 
        
      //   $(this.container).find(".accordion").html("");
      //   for (let adapter of json.adapter) {
      //     if (adapter.adapter_id == this.eventValue.adapterId) {
      //       $(this.container).find(".accordion").append(this.buildHTMLAdapter(adapter));
      //     }
      //   }

      // }, this));

      this.api.getOverview(this.eventValue.adapterId, $.proxy(function(json) { 
        
        $(this.container).find(".accordion").html("");
        for (let adapter of json.adapter) {
          if (adapter.adapter_id == this.eventValue.adapterId) {
            $(this.container).find(".accordion").append(this.buildHTMLAdapter(adapter));
          }
        }

        this.app.loadingInProgress(false);

      }, this));
      
    }

    buildHTMLAdapter(adapter) {

      let adapterInfo = adapter.versions.product_name;
      let rocTemp = 0;

      if (adapter.hw.roc_temperature) {
        rocTemp = adapter.hw.roc_temperature.celsius;
      }

      if (rocTemp) {
        adapterInfo += `<span class="badge badge-size bg-primary">${rocTemp}°c</span>`;
      }

      if (adapter._data) {
        if (adapter._data.temp == "danger") {
          adapterInfo = `<i class="bi bi-thermometer-high temperatore-sensor-high temperatore-sensor-icon"></i>` + adapterInfo;
        }
        else if (adapter._data.temp == "warning") {
          adapterInfo = `<i class="bi bi-thermometer-half temperatore-sensor-warning temperatore-sensor-icon"></i>` + adapterInfo;
        }
      }

      let html = '<div class="accordion" id="accordionOverview">';
      
      html += `<div class="accordion-item">

        <h4 class="accordion-header accordion-adapter d-flex" id="headingadapter">
          <div class="button-adapter collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapseadapter" aria-expanded="false" aria-controls="collapseadapter">
            <div class="row adapterinfo">
              <div class="col-auto me-auto"><i class="bi bi-gpu-card icon-card"></i> ${adapterInfo}</div>
            </div>
          </div>
        </h4>

        <div id="collapseadapter" class="accordion-collapse collapse" aria-labelledby="headingadapter">
          <div class="accordion-body">              
          </div>
        </div>

      </div>`;

      html += '</div>';

      return html;

    }

}

export { overview };