import { screen } from "../../assets/js/screen.js"

class satadrives extends screen {

    constructor(app) {
      super("satadrives", app);
      this.attach(this.eventRaised);
    }

    eventRaised(v) {
      
    }

    buildHTML() {
      
      const html = `<div class="accordion" id="accordionSataDrives"></div>`;

      $(this.container).html(html);

      this.api.getSataDrives($.proxy(function(json) { 
        
        $(this.container).find(".accordion").html("");
          var drives = json.physicaldrives;
          for (let drive of drives) {
            $(this.container).find(".accordion").append(satadrives.buildHTMLDrive(drive, this));
          }

      }, this));
      
    }

    static buildHTMLDrive(drive, refScreen, options) {

      var html = "";

      var pdinfo = `Slot: ${drive.slot_key} ${drive.vendor} ${drive.model} (WWN:${drive.wwn})`;
      pdinfo += `<span class="badge badge-size bg-primary">${drive.raw_size.hr}</span>`;
      pdinfo += `<span class="badge badge-size bg-primary">${drive.firmware_state}</span>`;
      pdinfo += `<span class="badge badge-size bg-primary">${drive.drive_temperature.celsius}°c</span>`;
      
/*  
   icon couleur
          vert => actif et bon => link-success
          gris => inactif => link-secondary
          rouge => pb => link-danger
          orange => warning => link-warning     
          bleu => spare => link-info
          jaune => init ou rebuild => link-rebuild
*/      

      var classIcon = "link-secondary";

      if (drive._data.state == "hotspare") {
        classIcon = "link-info";
      }
      else if (drive._data.state == "rebuild") {
        classIcon = "link-rebuild";
        var pct = (drive._data.rebuildprg) ? drive._data.rebuildprg : 0;
        pdinfo += `<div class="progress progress-rebuild text-center" role="progressbar" aria-label="Rebuild progression" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning overflow-visible text-dark text-center" style="width: ${pct}%">${pct}%</div></div>`;
      }
      else {

        if (drive._data.healthcheck == "ko") {
          classIcon = "link-danger";
        }
        else if (drive._data.healthcheck == "warning") {
          classIcon = "link-warning";  
        }
        else if (drive._data.state == "online") {
          classIcon = "link-success";
        }

      }

      if (drive._data.temp == "danger") {
        pdinfo = `<i class="bi bi-thermometer-high temperatore-sensor-high temperatore-sensor-icon"></i>` + pdinfo;
      }
      else if (drive._data.temp == "warning") {
        pdinfo = `<i class="bi bi-thermometer-half temperatore-sensor-warning temperatore-sensor-icon"></i>` + pdinfo;
      }

      html += `<div class="accordion-item">

                <h4 class="accordion-header accordion-drive d-flex" id="heading${drive.device_id}">
                  <div class="button-drive collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${drive.device_id}" aria-expanded="false" aria-controls="collapse${drive.device_id}">
                    <div class="row physicaldriveinfo">
                      <div class="col-auto me-auto"><i class="bi bi-device-hdd-fill icon-drive ${classIcon}"></i> ${pdinfo}</div>
                    </div>
                  </div>
                </h4>

                <div id="collapse${drive.device_id}" class="accordion-collapse collapse" aria-labelledby="heading${drive.device_id}">
                  <div class="accordionpdrive-body">              
                  </div>
                </div>

              </div>`;
    
      var $html = $(html);

      return $html;

    }

}

export { satadrives };