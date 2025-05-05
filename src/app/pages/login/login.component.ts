import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { ToastService } from 'src/app/services/toast.service';

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {

    constructor(
        private MasterService: MasterServicesService,
        private ToastService: ToastService,
        private router: Router,
    ) { }

    public login_obj: any = {};
    public hide: boolean = false;
    login_obj_set() {
        this.login_obj = {
            email_address: "",
            password: "",
        }
    }

    emailPattern = "^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$";

    ngOnInit() {
        this.login_obj_set();
    }

    public isSubmitted: boolean = false;
    public button_text: string = "Sign In";
    SubmitLoginForm() {
        if (!this.isSubmitted) {
            this.isSubmitted = true;
            this.button_text = "Please Wait...";

            const obj = this.login_obj;
            const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

            this.MasterService.login_form(mapped).subscribe(res => {
                var response = JSON.parse(JSON.stringify(res));
                if (response.success) {
                    this.MasterService.setObjservableUser(JSON.stringify(response.data));
                    this.MasterService.setObjservableToken(response.data.token);
                    this.ToastService.success(response.message);
                    this.router.navigate(['/']);
                    this.login_obj_set();
                } else {
                    this.ToastService.error(response.message);
                }
                this.isSubmitted = false;
                this.button_text = "Sign In";
            }, error => {
                this.isSubmitted = false;
                this.button_text = "Sign In";
                this.login_obj_set();
                this.ToastService.error('Opps...something went wrong, Plesae try again.');
            })
        }
    }

}

