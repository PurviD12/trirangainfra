import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-profile-update',
  templateUrl: './profile-update.component.html',
  styleUrls: ['./profile-update.component.css']
})
export class ProfileUpdateComponent implements OnInit {

  constructor(
      private MasterService: MasterServicesService,
      private ToastService: ToastService,
      private router: Router,
      private route: ActivatedRoute,
  ) { }

  emailPattern = "^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$";
  public activePath: any = "";
  public hide: boolean = false;
  public hide2: boolean = false;

  ngOnInit() {
      this.route.url.subscribe(urlSegments => {
          this.activePath = urlSegments.map(segment => segment.path).join('/');
          if (this.activePath == "profile/update") {
              this.details_get();
          }
      });
  }

  public profile_obj: any = {};
  public isSubmitted: boolean = false;
  public button_text: string = "Submit";
  SubmitProfileForm() {
      if (!this.isSubmitted) {
          this.isSubmitted = true;
          this.button_text = "Please Wait...";

          const obj = this.profile_obj;
          const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

          this.MasterService.update_profile_all_users_set(mapped).subscribe(res => {
              var response = JSON.parse(JSON.stringify(res));
              if (response.success) {
                  this.MasterService.setObjservableUser(JSON.stringify(response.data));
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

  public isLoadingDetails: boolean = false;
  details_get() {
      this.isLoadingDetails = true;
      this.profile_obj = {};

      const obj: { [key: string]: any } = {};
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.admin_details_get(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.profile_obj = response.data;
              this.profile_obj.contact_no = this.profile_obj.contact_no.replace("91 ", "");
          }
          this.isLoadingDetails = false;
      }, error => {
          this.isLoadingDetails = false;
      })
  }

  SubmitProfilePasswordForm() {
      if (!this.isSubmitted) {
          if (this.profile_obj.confirm_password !== this.profile_obj.password) {
              this.profile_obj.confirm_password = "";
              this.ToastService.error('Passwords do not matched, Please re enter again!');
          } else {
              this.isSubmitted = true;
              this.button_text = "Please Wait...";

              const obj = this.profile_obj;
              const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

              this.MasterService.master_user_password(mapped).subscribe(res => {
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
  }

  @ViewChild('fileInput', { static: false }) fileInputRef!: ElementRef<HTMLInputElement>;
  public previewImageUrl: any = "";
  onFileSelected(event: any) {
      const file: File = event.target.files[0];
      if (file && (file.type != "image/jpeg" && file.type != "image/jpg" && file.type != "image/png")) {
          this.profile_obj.profile_image_file = "";
      } else if (file) {
          this.profile_obj.profile_image = file;
          const reader = new FileReader();
          reader.onload = (e: any) => {
              this.previewImageUrl = e.target.result;
          };
          reader.readAsDataURL(file);
      }

      const maxFileSize = 1 * 1024 * 1024;
      if (file && file.size > maxFileSize) {
          this.profile_obj.profile_image_file = "";
          this.ToastService.error("Opps... You can upload image up to 1MB in size.");
      }
  }

  openFileInput() {
      this.fileInputRef.nativeElement.click();
  }
}
