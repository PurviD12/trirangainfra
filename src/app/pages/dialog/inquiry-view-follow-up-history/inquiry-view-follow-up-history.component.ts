import { Component, Inject, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MasterServicesService } from 'src/app/services/master-services.service';

@Component({
    selector: 'app-inquiry-view-follow-up-history',
    templateUrl: './inquiry-view-follow-up-history.component.html',
    styleUrls: ['./inquiry-view-follow-up-history.component.css']
})
export class InquiryViewFollowUpHistoryComponent implements OnInit {

    constructor(
        public MasterService: MasterServicesService,
        public dialogRef: MatDialogRef<InquiryViewFollowUpHistoryComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any = {}
    ) {
        this.followUp_obj = data;
    }

    public followUp_obj: any = {};

    ngOnInit() {
        this.history_obj.id = this.followUp_obj.inquiry_id;
        this.history_get();
    }

    closeDialog(result: boolean) {
        this.dialogRef.close(result);
    }

    public isLoading : boolean = false;
    public history_list : any = {
        inquiry: {},
        history: [],
    }
    public history_obj: any = {};
    history_get() {
        this.isLoading = true;
        this.history_list = {};

        const obj = this.history_obj;
        const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

        this.MasterService.followUp_history(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.history_list = response.data;
            }
            this.isLoading = false;
        }, error => {
            this.isLoading = false;
        })
    }

}
