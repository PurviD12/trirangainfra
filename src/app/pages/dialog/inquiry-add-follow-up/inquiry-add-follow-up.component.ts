import { Component, Inject, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';

@Component({
    selector: 'app-inquiry-add-follow-up',
    templateUrl: './inquiry-add-follow-up.component.html',
    styleUrls: ['./inquiry-add-follow-up.component.css']
})
export class InquiryAddFollowUpComponent implements OnInit {

    public followUp_obj: any = {
        status_list: []
    };
    constructor(
        private MasterService: MasterServicesService,
        private ToastService: ToastService,
        public dialogRef: MatDialogRef<InquiryAddFollowUpComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any = {}
    ) {
        this.followUp_obj = data;
        const today = new Date();
        this.status_change();
        if (!this.followUp_obj.follow_up_date_display) {
            this.followUp_obj.follow_up_date_display = new Date();
        }
    }

    public minDate = new Date();

    ngOnInit() {
    }

    closeDialog(result: boolean) {
        this.dialogRef.close(result);
    }

    public show_obj: any = {};
    status_change() {
        var status_get = this.followUp_obj.status;
        var show_obj_get = this.followUp_obj.status_list.filter(function (val: any) {
            return val.name == status_get;
        })[0];

        this.show_obj = show_obj_get;
    }

    public isSubmitted: boolean = false;
    public button_text: string = "Submit";
    SubmitFollowUpForm() {
        if (!this.isSubmitted) {
            this.isSubmitted = true;
            this.button_text = "Please Wait...";
            // this.followUp_obj.follow_up_date = this.followUp_obj.follow_up_date_display ? this.followUp_obj.follow_up_date_display.getTime() / 1000 : "";
            this.followUp_obj.follow_up_date = this.followUp_obj.follow_up_date_display ? Number((this.followUp_obj.follow_up_date_display.getTime() / 1000).toFixed(0)) : "";

            // this.followUp_obj.follow_up_date = this.followUp_obj.follow_up_date_display ? Number((this.followUp_obj.follow_up_date_display.getTime() / 1000).toFixed(0)) : "";

            // console.log(this.followUp_obj);
            // return false;
            const obj = this.followUp_obj;
            const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

            this.MasterService.addFollowUp_save(mapped).subscribe(res => {
                var response = JSON.parse(JSON.stringify(res));
                if (response.success) {
                    this.ToastService.success(response.message);
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

    @ViewChild('fileInput', { static: false }) fileInputRef!: ElementRef<HTMLInputElement>;

    onFileSelected(event: any) {
        const file: File = event.target.files[0];
        this.followUp_obj.attachment = file;
        if (file && file.type != "application/pdf") {
            this.followUp_obj.attachment_file = "";
        }

        const maxFileSize = 10 * 1024 * 1024;
        if (file.size > maxFileSize) {
            this.followUp_obj.attachment_file = "";
            this.ToastService.error("Opps.. You can upload file size upto 10MB");
        }
    }

    openFileInput() {
        this.fileInputRef.nativeElement.click();
    }
}
