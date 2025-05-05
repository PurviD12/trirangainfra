import { Component,OnInit } from '@angular/core';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';
import { ActivatedRoute, Router } from '@angular/router';
import { MasterPageComponent } from 'src/app/pages/master-page/master-page.component';
@Component({
  selector: 'app-team-add',
  templateUrl: './team-add.component.html',
  styleUrls: ['./team-add.component.css']
})
export class TeamAddComponent implements OnInit {

  constructor(
      private MasterService: MasterServicesService,
      private ToastService: ToastService,
      private router: Router,
      private route: ActivatedRoute,
      private masterPageComponent: MasterPageComponent,
  ) { }

  public master_user_id: any = "";
  emailPattern = "^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$";
  public hide: boolean = false;
  public hide2: boolean = false;

  public router_obj: any = {};
  ngOnInit() {
      this.route.paramMap.subscribe(params => {
          if (params.get('master_user_id')) {
              this.master_user_id = atob(params.get('master_user_id')!);
              if (this.master_user_id) {
                  this.team_get();
              }
          }
      });

      this.router_obj = this.masterPageComponent.router_obj;
  }

  public team_obj: any = {
      is_active: "1",
      type: "sales executive",
  };
  public isSubmitted: boolean = false;
  public button_text: string = "Submit";
  SubmitTeamForm() {
      if (!this.isSubmitted) {
          this.isSubmitted = true;
          this.button_text = "Please Wait...";
          
          const obj = this.team_obj;
          const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

          this.MasterService.team_save(mapped).subscribe(res => {
              var response = JSON.parse(JSON.stringify(res));
              if (response.success) {
                  this.ToastService.success(response.message);
                  this.router.navigate(['/team']);
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

  SubmitTeamPasswordForm() {
      if (!this.isSubmitted) {
          if (this.team_obj.confirm_password !== this.team_obj.password) {
              this.team_obj.confirm_password = "";
              this.ToastService.error('Passwords do not matched, Please re enter again!');
          } else {
              this.isSubmitted = true;
              this.button_text = "Please Wait...";
              
              const obj = this.team_obj;
              const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));
  
              this.MasterService.team_password(mapped).subscribe(res => {
                  var response = JSON.parse(JSON.stringify(res));
                  if (response.success) {
                      this.ToastService.success(response.message);
                      this.router.navigate(['/team']);
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

  public isLoadingDetails: boolean = false;
  team_get() {
      this.isLoadingDetails = true;
      this.team_obj = {};

      const obj: { [key: string]: any } = {
          master_user_id: this.master_user_id
      };
      const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

      this.MasterService.team_list_get(mapped).subscribe(res => {
          var response = JSON.parse(JSON.stringify(res));
          if (response.success) {
              this.team_obj = response.data[0];
              this.team_obj.contact_no = this.team_obj.contact_no.replace("91 ", "");
              this.team_obj.is_active = this.team_obj.is_active.toString();
          }
          this.isLoadingDetails = false;
      }, error => {
          this.isLoadingDetails = false;
      })
  }
}

