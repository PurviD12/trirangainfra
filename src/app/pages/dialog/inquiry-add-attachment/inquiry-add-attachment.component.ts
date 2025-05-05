import { Component, Inject, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';

@Component({
    selector: 'app-inquiry-add-attachment',
    templateUrl: './inquiry-add-attachment.component.html',
    styleUrls: ['./inquiry-add-attachment.component.css']
})
export class InquiryAddAttachmentComponent implements OnInit {

    public attachment_obj: any = {};
    constructor(
        private MasterService: MasterServicesService,
        private ToastService: ToastService,
        public dialogRef: MatDialogRef<InquiryAddAttachmentComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any = {}
    ) {
        this.attachment_obj = data;
    }

    ngOnInit() {
    }

    closeDialog(result: any) {
        this.dialogRef.close(result);
    }

    public isSubmitted: boolean = false;
    public button_text: string = "Submit";
    SubmitAttachmentForm() {
        if (!this.isSubmitted) {
            this.closeDialog(this.attachment_obj);
        }
    }

    @ViewChild('fileInput', { static: false }) fileInputRef!: ElementRef<HTMLInputElement>;

    onFileSelected(event: any) {
        const file: File = event.target.files[0];
        this.attachment_obj.attachment = "";
        if (file) {
            this.attachment_obj.attachment = file;
        }
        if (file && file.type != "application/pdf") {
            this.attachment_obj.attachment_file = "";
        }

        const maxFileSize = 10 * 1024 * 1024;
        if (file && file.size > maxFileSize) {
            this.attachment_obj.attachment_file = "";
            this.attachment_obj.attachment_full = "";
            this.attachment_obj.attachment = "";
            this.fileInputRef.nativeElement.value = "";
            this.ToastService.error("Opps.. You can upload file size upto 10MB");
        }
    }

    openFileInput() {
        this.fileInputRef.nativeElement.click();
    }

}
