import { Component, OnInit, HostListener } from '@angular/core';
import { Router, ActivatedRoute, NavigationEnd } from '@angular/router';
import { Location } from '@angular/common';
import { filter } from 'rxjs/operators';
import { AppComponent } from 'src/app/app.component';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { interval } from 'rxjs';

@Component({
    selector: 'app-master-page',
    templateUrl: './master-page.component.html',
    styleUrls: ['./master-page.component.css']
})
export class MasterPageComponent implements OnInit {

    constructor(
        private routes: Router,
        private location: Location,
        private activatedRoute: ActivatedRoute,
        private appComponent: AppComponent,
        private MasterService: MasterServicesService,
    ) { }

    public router_obj: any = {};
    public store_data: any = {};
    ngOnInit() {
        if (this.location.path() == "") {
            this.routes.navigate(['dashboard']);
        }
        this.store_data = JSON.parse(localStorage.getItem("LoggedInUser")!);
        this.routes.events.pipe(
            filter(event => event instanceof NavigationEnd),
        ).subscribe(() => {
            const rt = this.getChild(this.activatedRoute);
            rt.data.subscribe((data: any) => {
                this.router_obj = data;
                this.check_path();
            });
        });
        if (!this.router_obj.title) {
            this.router_obj = this.appComponent.router_obj;
        }
        this.check_path();
        this.admin_details();
        interval(100000).subscribe(x => {
            this.admin_details_check();
        });
    }

    getChild(activatedRoute: ActivatedRoute): any {
        if (activatedRoute.firstChild) {
            return this.getChild(activatedRoute.firstChild);
        } else {
            return activatedRoute;
        }
    }

    check_path() {
        if (this.store_data.type == "sales executive" && this.router_obj.disabled_sub_user == 1) {
            this.routes.navigate(['/dashboard']);
        }
    }

    public menu_open: boolean = false;
    menu_toggle(flag: any) {
        this.menu_open = flag;
    }

    admin_details() {
        this.MasterService.admin_details_get("").subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.MasterService.setObjservableUser(JSON.stringify(response.data));
            } else {
                this.MasterService.removeObjservableUser();
                this.MasterService.removeObjservableToken();
                this.routes.navigate(['login']);
            }
        }, error => {
            this.MasterService.removeObjservableUser();
            this.MasterService.removeObjservableToken();
            this.routes.navigate(['login']);
        })
    }

    admin_details_check() {
        // this.MasterService.admin_details_check_get("").subscribe(res => {
        //     var response = JSON.parse(JSON.stringify(res));
        //     if (response.success) {

        //     } else {
        //         this.MasterService.removeObjservableUser();
        //         this.MasterService.removeObjservableToken();
        //         this.routes.navigate(['login']);
        //     }
        // }, error => {
        //     this.MasterService.removeObjservableUser();
        //     this.MasterService.removeObjservableToken();
        //     this.routes.navigate(['login']);
        // })
    }

    // @HostListener('window:blur')
    // onWindowBlur() {
    //     this.admin_details_check();
    //     this.admin_details();
    // }

    // @HostListener('window:focus')
    // onWindowFocus() {
    //     this.admin_details_check();
    //     this.admin_details();
    // }

    // @HostListener('document:visibilitychange', ['$event'])
    // onVisibilityChange(event: Event) {
    //     if (document.visibilityState === 'visible') {
    //         this.admin_details_check();
    //         this.admin_details();
    //     } else {
    //         this.admin_details_check();
    //         this.admin_details();
    //     }
    // }

    @HostListener('window:focus', ['$event'])
    onWindowFocus(event: FocusEvent) {
        this.admin_details_check();
        // this.admin_details();
    }

}
