import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-import-leads',
  templateUrl: './import-leads.component.html',
  styleUrls: ['./import-leads.component.css']
})
export class ImportLeadsComponent implements OnInit {

  constructor(
      private MasterService: MasterServicesService,
      private ToastService: ToastService,
      private router: Router,
      private route: ActivatedRoute,
  ) { }

  ngOnInit() {
  }

  public import_obj: any = {};
  public isSubmitted: boolean = false;
  public button_text: string = "Submit";
  SubmitImportForm() {
      if (!this.isSubmitted) {
          this.isSubmitted = true;
          this.button_text = "Please Wait...";

          const obj = this.import_obj;
          const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

          this.MasterService.import_save(mapped).subscribe(res => {
              var response = JSON.parse(JSON.stringify(res));
              if (response.success) {
                  this.ToastService.success(response.message);
                  this.router.navigate(['/dashboard']);
              } else {
                  this.ToastService.error(response.message);
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
      this.import_obj.import_file = "";
      if (file && file.type != "application/vnd.ms-excel") {
          this.import_obj.attachment_file = "";
      }

      const maxFileSize = 10 * 1024 * 1024;
      if (file.size > maxFileSize) {
          this.import_obj.attachment_file = "";
          this.ToastService.error("Oops... You can upload files up to 10MB in size.");
      } else {
          this.import_obj.import_file = file;
      }
  }

  openFileInput() {
      this.fileInputRef.nativeElement.click();
  }
}
