import { screen } from "../../assets/js/screen.js"
import { physicaldrives } from "../../assets/js/physicaldrives.js"

class virtualdrives extends screen {

    constructor(app) {
      super("virtualdrives", app);
      this.attach(this.eventRaised);
    }

    eventRaised(v) {
      

    }

    buildHTML() {

      const html = `<div class="accordion" id="accordionVirtualDrives"></div>
                    <div class="mt-2 d-grid gap-2 d-md-flex justify-content-md-end"><button class="btn btn-sm btn-primary btn-createvd">Create</button></div>`;

      $(this.container).html(html);

      $(this.container).find(".btn-createvd").on("click", $.proxy(function() {

        //Retrive phyisical drives and display only HDD not assigned
        


      }, this));

      this.api.getAdapterConfig(this.eventValue.adapterId, $.proxy(function(json) { 

        $(this.container).find(".accordion").html("");
        const vgroups = json.adapters[0].disk_group;
        for (let vg of vgroups) {
          for(let span of vg.span) {
            for(let vdrive of span.virtual_drive) {
              $(this.container).find(".accordion").append(virtualdrives.buildHTMLVirtualDrive(vdrive, this));
            }
          }
        }

        this.app.loadingInProgress(false);

      }, this));

  }

  static buildHTMLVirtualDrive(vdrive, refScreen) {

    let vdinfo = "VD:" + vdrive.virtual_drive_id + ' - ' + vdrive.name;
    vdinfo += `<span class="badge badge-size bg-primary">${vdrive.size}</span>`;
    vdinfo += `<span class="badge badge-size bg-primary">raid${vdrive.raid}</span>`;

    let classIcon = "link-success";

    if (vdrive._data.state_optimal) {
      vdinfo += `<span class="badge badge-size bg-success">${vdrive.state}</span>`;
    }
    else {
      classIcon = "link-danger";
      vdinfo += `<span class="badge badge-size bg-warning">${vdrive.state}</span>`;
    }

    if (vdrive._data.bginitprg !== null && vdrive._data.bginitprg !== undefined) {
      classIcon = "link-warning";
      var pct = (vdrive._data.bginitprg) ? vdrive._data.bginitprg : 0;
      vdinfo += `<div class="progress progress-rebuild text-center" role="progressbar" aria-label="background initialization" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning overflow-visible text-dark text-center" style="width: ${pct}%">Init ${pct}%</div></div>`;
    }

    if (vdrive._data.fginitprg !== null && vdrive._data.fginitprg !== undefined) {
      classIcon = "link-secondary";
      var pct = (vdrive._data.fginitprg) ? vdrive._data.fginitprg : 0;
      vdinfo += `<div class="progress progress-rebuild text-center" role="progressbar" aria-label="background initialization" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning overflow-visible text-dark text-center" style="width: ${pct}%">FG.Init ${pct}%</div></div>`;
    }

    if (vdrive._data.ccprg !== null && vdrive._data.ccprg !== undefined) {
      var pct = (vdrive._data.ccprg) ? vdrive._data.ccprg : 0;
      vdinfo += `<div class="progress progress-rebuild text-center" role="progressbar" aria-label="background initialization" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-info overflow-visible text-dark text-center" style="width: ${pct}%">C.Check ${pct}%</div></div>`;
    }

    let html = `<div class="accordion-item">

      <h4 class="accordion-header accordion-adapter d-flex" id="headingvirtualdrive${vdrive.virtual_drive_id}">
        <div class="button-adapter collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapsevirtualdrive${vdrive.virtual_drive_id}" aria-expanded="false" aria-controls="collapsevirtualdrive${vdrive.virtual_drive_id}">
          <div class="row virtualdriveinfo">
            <div class="col-auto me-auto"><i class="bi bi-hdd${(vdrive._data.boot) ? '-fill': ''} icon-vdrive ${classIcon}"></i> ${vdinfo}</div>
          </div>
        </div>
        <div class="col-auto">
          <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle btn-action" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-menu-button-fill"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item drive-action" data-action="locate" data-option="start" href="#">Start Consistency Check</a></li>
              <li><a class="dropdown-item drive-action" data-action="locate" data-option="start" href="#">Stop Consistency Check</a></li>
            </ul>
          </div>
        </div>        
      </h4>

      <div id="collapsevirtualdrive${vdrive.virtual_drive_id}" class="accordion-collapse collapse" aria-labelledby="headingvirtualdrive${vdrive.virtual_drive_id}">
        <div class="accordionvdrive-body">            
        </div>
      </div>

    </div>`;

    html += '</div>';

    var $html = $(html);

    

    for(let pdrive of vdrive.physical_drive) {
      $html.find(".accordionvdrive-body").append(physicaldrives.buildHTMLDrive(pdrive, refScreen));
    }

    return $html;

  }

}

export { virtualdrives };