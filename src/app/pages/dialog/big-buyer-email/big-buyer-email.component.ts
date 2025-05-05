import { COMMA, ENTER } from '@angular/cdk/keycodes';
import { Component, Inject, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';
import { MatChipInputEvent } from '@angular/material/chips';
// import * as ClassicEditor from '@ckeditor/ckeditor5-build-classic';

export interface Fruit {
    name: string;
}

@Component({
    selector: 'app-big-buyer-email',
    templateUrl: './big-buyer-email.component.html',
    styleUrls: ['./big-buyer-email.component.css']
})
export class BigBuyerEmailComponent implements OnInit {

    constructor(
        private MasterService: MasterServicesService,
        private ToastService: ToastService,
        public dialogRef: MatDialogRef<BigBuyerEmailComponent>,
        @Inject(MAT_DIALOG_DATA) public data_get: any = {}
    ) {
        this.email_get = data_get;
        this.email_obj.inquiry_id = this.email_get.inquiry_id;
        this.email_to_array = [{ name: this.email_get.email_address }];
        // this.email_to_array = [
        //     { name: this.email_get.email_address || 'default@example.com' }
        // ];
    }

    public email_get: any = {};
    public email_obj: any = {
        email_content: "",
        email_subject: "",
    };
    public email_to_array: any = [];
    emailPattern = "^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$";

    ngOnInit() {

    }

    public isSubmitted: boolean = false;
    public button_text: string = "Submit";
    SubmitEmailForm() {
        if (!this.isSubmitted) {
            this.isSubmitted = true;
            this.button_text = "Please Wait...";
            this.email_obj.email_to = [];
            this.email_to_array.forEach((val: any, key: any) => {
                this.email_obj.email_to.push(val.name);
            });
            const obj = this.email_obj;
            const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

            this.MasterService.big_buyer_email_send(mapped).subscribe(res => {
                var response = JSON.parse(JSON.stringify(res));
                if (response.success) {
                    this.ToastService.success(response.message);
                    if (response.other_info) {
                        this.ToastService.info(response.other_info_message);
                    }
                    this.closeDialog(true);
                } else {
                    this.ToastService.error(response.message);
                    // this.closeDialog(false);
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

    visible = true;
    selectable = true;
    removable = true;
    addOnBlur = true;
    readonly separatorKeysCodes: number[] = [ENTER, COMMA];
    fruits: Fruit[] = [
        { name: 'Lemon' },
        { name: 'Lime' },
        { name: 'Apple' },
    ];

    add(event: MatChipInputEvent): void {
        const input = event.input;
        const value = event.value;

        // Add our fruit
        if ((value || '').trim()) {
            this.email_to_array.push({ name: value.trim() });
        }

        // Reset the input value
        if (input) {
            input.value = '';
        }
    }

    remove(fruit: Fruit): void {
        const index = this.email_to_array.indexOf(fruit);

        if (index >= 0) {
            this.email_to_array.splice(index, 1);
        }
    }

    @ViewChild('fileInput', { static: false }) fileInputRef!: ElementRef<HTMLInputElement>;

    onFileSelected(event: any) {
        const file: File = event.target.files[0];
        this.email_obj.email_attachment = file;
        if (file && file.type != "application/pdf") {
            this.email_obj.attachment_file = "";
        }

        const maxFileSize = 10 * 1024 * 1024;
        if (file.size > maxFileSize) {
            this.email_obj.attachment_file = "";
            this.ToastService.error("Opps.. You can upload file size upto 10MB");
        }
    }

    openFileInput() {
        this.fileInputRef.nativeElement.click();
    }
    // public Editor = ClassicEditor;
    // public editorData: string = '';
    public editorConfig = {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList'],
    };
    // configEditor = {
    //     removeButtons: 'Print,Preview,NewPage,Save,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Find,SelectAll,Scayt,Checkbox,TextField,Textarea,Radio,Form,Select,Button,ImageButton,HiddenField,Replace,CopyFormatting,Outdent,Indent,Blockquote,CreateDiv,BidiLtr,BidiRtl,Language,Anchor,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Maximize,About,FontSize',
    //     allowedContent: true,
    //     // filebrowserUploadUrl: environment.apiUrl + 'uploadImages/',
    //     height: '46vh',
    //     toolbarGroups: [
    //         { name: 'document', groups: ['mode', 'document', 'doctools'] },
    //         { name: 'editing', groups: ['find', 'selection', 'spellchecker', 'editing'] },
    //         { name: 'basicstyles', groups: ['basicstyles', 'cleanup'] },
    //         { name: 'styles', groups: ['styles'] },
    //         { name: 'paragraph', groups: ['list', 'indent', 'blocks'] },
    //         '/',
    //         { name: 'clipboard', groups: ['clipboard', 'undo'] },
    //         { name: 'paragraph', groups: ['align', 'bidi', 'paragraph'] },
    //         { name: 'forms', groups: ['forms'] },
    //         { name: 'links', groups: ['links'] },
    //         { name: 'colors', groups: ['colors'] },
    //         { name: 'insert', groups: ['insert'] },
    //         { name: 'tools', groups: ['tools'] },
    //         { name: 'others', groups: ['others'] },
    //     ]
    // }
}