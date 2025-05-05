import { Component, Inject, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';

@Component({
    selector: 'app-transfer-lead',
    templateUrl: './transfer-lead.component.html',
    styleUrls: ['./transfer-lead.component.css']
})
export class TransferLeadComponent implements OnInit {

    constructor(
        private MasterService: MasterServicesService,
        private ToastService: ToastService,
        public dialogRef: MatDialogRef<TransferLeadComponent>,
        @Inject(MAT_DIALOG_DATA) public data_get: any = {}
    ) {
        this.transfer_obj_get = data_get;
        this.team_assign_get_list();
    }

    ngOnInit() {
    }

    public transfer_obj_get: any = {};
    public transfer_obj: any = {};
    public assign_team_list: any = [];
    public isLoading: boolean = false;
    team_assign_get_list() {
        this.isLoading = true;
        this.assign_team_list = [];

        const obj = this.transfer_obj_get;
        const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));
        this.transfer_obj_get.export = 0;
        this.MasterService.team_assign_get(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.assign_team_list = response.data;
            }
            this.isLoading = false;
        }, error => {
            this.isLoading = false;
        })
    }

    public isSubmitted: boolean = false;
    public button_text: string = "Submit";
    SubmitTransferForm() {
        if (!this.isSubmitted) {
            this.isSubmitted = true;
            this.button_text = "Please Wait...";
            this.transfer_obj.ids = this.transfer_obj_get.ids;
            const obj = this.transfer_obj;
            const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

            this.MasterService.lead_assign(mapped).subscribe(res => {
                var response = JSON.parse(JSON.stringify(res));
                if (response.success) {
                    this.ToastService.success(response.message);
                    if (response.other_info) {
                        this.ToastService.info(response.other_info_message);
                    }
                    this.closeDialog(true);
                } else {
                    this.ToastService.error(response.message);
                    this.closeDialog(false);
                }
                this.isSubmitted = false;
                this.button_text = "Submit";
            }, error => {
                this.isSubmitted = false;
                this.button_text = "Submit";
                this.ToastService.error('Opps...something went wrong, Plesae try again.');
            })
        }
    }

    closeDialog(result: boolean) {
        this.dialogRef.close(result);
    }
}
