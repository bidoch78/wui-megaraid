import { screen } from "../../assets/js/screen.js"

class physicaldrives extends screen {

    constructor(app) {
      super("physicaldrives", app);
      this.attach(this.eventRaised);
    }

    eventRaised(v) {
      
    }

    buildHTML() {
      
      const html = `<div class="accordion" id="accordionDrives"></div>`;

      $(this.container).html(html);

      this.api.getPhysicalDrives(this.eventValue.adapterId, $.proxy(function(json) { 
        
        $(this.container).find(".accordion").html("");
        var drives = json.adapters[0].physicaldrives;
        for (let drive of drives) {
          $(this.container).find(".accordion").append(physicaldrives.buildHTMLDrive(drive, this));
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
        add label avec progression
      */

      var classIcon = "link-secondary";

      if (drive._data.state == "hotspare") {
        classIcon = "link-info";
      }
      else if (drive._data.state == "rebuild") {
        classIcon = "link-rebuild";
        var pct = (drive._data.rebuildprg) ? drive._data.rebuildprg : 0;
        pdinfo += `<div class="progress progress-rebuild text-center" role="progressbar" aria-label="Rebuild progression" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning overflow-visible text-dark text-center" style="width: ${pct}%">Rebuild ${pct}%</div></div>`;
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
                  <div class="col-auto">
                    <div class="dropdown">
                      <button class="btn btn-sm btn-primary dropdown-toggle btn-action" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-menu-button-fill"></i>
                      </button>
                      <ul class="dropdown-menu">
                        <li><a class="dropdown-item drive-action" data-action="locate" data-option="start" href="#">Start locating drive (LED)</a></li>
                        <li><a class="dropdown-item drive-action" data-action="locate" data-option="stop" href="#">Stop locating drive (LED)</a></li>
                        <li><a class="dropdown-item drive-action" data-action="makegood" href="#">Change state to Unconfigured Good</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item drive-action" data-action="prprmv" data-option="" href="#">Set state to Offline (Prepare for Removal)</a></li>
                        <li><a class="dropdown-item drive-action" data-action="prprmv" data-option="undo" href="#">Set state to Online</a></li>
                        <li><a class="dropdown-item drive-action" data-action="markmissing" data-option="" href="#">Set as Missing</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item disabled" data-action="" data-option="" href="#">Use as HotSpare</a></li>
                        <li><a class="dropdown-item disabled" data-action="" data-option="" href="#">Remove HotSpare</a></li>
                      </ul>
                    </div>
                  </div>
                </h4>

                <div id="collapse${drive.device_id}" class="accordion-collapse collapse" aria-labelledby="heading${drive.device_id}">
                  <div class="accordionpdrive-body">              
                  </div>
                </div>

              </div>`;
    
      /*

                /*

      Firmware state: 
        Hotspare, Spun Up
        Online, Spun Up
        Unconfigured(good), Spun Up
        Unconfigured(bad)
        Rebuild
        Failed
        Unconfigured(good), Spun down


        Firmware state
        media_error_count
        other_error_count
        last_predictive_failure_event_seq_number
        drive_has_flagged_a_smart_alert
        drive_is_assigned
        Slot Number
        device_id
        enclosure_device_id
        emergency_spare
        shield_counter


Foreign State: Foreign => a previous config is find

      */      

      var $html = $(html);

      $html.find(".drive-action").on("click", $.proxy(function(info, e) { 

        var $this = $(e.currentTarget);
        var text = "";
        var data = {  'info': info, 
                      'post': {
                        'command': $this.attr("data-action"), 
                        'pid': drive.enclosure_device_id + ":" + drive.slot_number,
                        'options': $this.attr("data-option") 
                      }
                    };

        switch($this.attr("data-action")) {
          case "locate":
              text = ($this.attr("data-option") == "start")
                      ? "Locate the drive(s) for the selected controller(s) and activate the drive activity LED" 
                      : "Locate the drive(s) for the selected controller(s) and deactivate the drive activity LED";
              break;
          case "makegood":
              text = "Change the state of a drive from Unconfigured-Bad to Unconfigured-Good";
              break;
          case "prprmv":
              text = ($this.attr("data-option") == "undo") 
                      ? "Reactivate Unconfigured drive" 
                      : "Remove Unconfigured drive";
              break;
          case "markmissing":
              text = "Marks the configured drive as missing";
              break;
        }

        text = text + `<br>Drive: Slot: ${drive.slot_number} ${drive.vendor} ${drive.model} (WWN:${drive.wwn})`;

        screen.displayModal({
          'alwaysCloseWithButtons': true,
          'body': text,
          'buttons': [
              { 'caption': 'Cancel', 'class': 'btn-outline-secondary', closemodal: true, 'visible': true },
              { 'caption': 'Send', 'class': 'btn-outline-danger', closemodal: false, 'visible': true, 'onclick': $.proxy(function(apidata, dt) {
                
                dt.updateHTML(`<div class="loading_data"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div>`);
                dt.displayFooterButtons();

                  alert("need to check");

                // this.api.sendPhysicalDrivesCommand(this.eventValue.adapterId, { 'post': apidata.post }).then($.proxy(function(json) { 
                  
                //   if (json && (json.success === 0 || json.code != 0)) {
                //     var error = "error...";
                //     if (json.return) error = json.return.join("<br>");
                //     if (json.message) error = json.message;
                //     throw new Error(error);
                //   }
            
                //   var msg = "";
                //   if (json.return) msg = json.return.join("<br>");
                //   dt.updateHTML(`<div class="alert alert-success" role="alert">${msg}</div>`);

                // }, this)).
                // catch($.proxy(function(err) {
                //   dt.updateHTML(`<div class="alert alert-danger" role="alert">${err}</div>`);
                // }, this)).
                // finally(function() {
                //   dt.displayFooterButtons([
                //     { 'caption': 'Close', 'class': 'btn-outline-secondary', closemodal: true, 'visible': true }
                //   ]);
                // })

              }, this, data)}

          ]

        });

       }, refScreen, { drive: drive }));

      return $html;

    }

}

export { physicaldrives };