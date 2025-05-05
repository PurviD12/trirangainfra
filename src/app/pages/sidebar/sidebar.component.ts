import { Component, OnInit, HostListener, ViewChildren, QueryList, ViewEncapsulation, ViewChild } from '@angular/core';
import { MatMenuTrigger } from '@angular/material/menu';
import { Router, ActivatedRoute, NavigationEnd } from '@angular/router';
import { MasterServicesService } from 'src/app/services/master-services.service';
import { MasterPageComponent } from '../master-page/master-page.component';
import { Title } from '@angular/platform-browser';
import { filter } from 'rxjs/operators';
import { AppComponent } from 'src/app/app.component';

@Component({
    selector: 'app-sidebar',
    templateUrl: './sidebar.component.html',
    styleUrls: ['./sidebar.component.css'],
    encapsulation: ViewEncapsulation.None
})
export class SidebarComponent implements OnInit {

    constructor(
        private MasterServices: MasterServicesService,
        public router: Router,
        private masterPageComponent: MasterPageComponent,
        private appComponent: AppComponent,
        private activatedRoute: ActivatedRoute,
        private titleService: Title
    ) {
        // this.masterPageComponent_call = masterPageComponent;
    }
    // public masterPageComponent_call: any = {};

    public menu_open_f: boolean = false;
    click_menu_from_master() {
        if (this.menu_open_f) {
            this.menu_open_f = false;
        } else {
            this.menu_open_f = true;
        }
        this.masterPageComponent.menu_toggle(this.menu_open_f);
    }

    public router_obj: any = {};
    public store_data: any = {};
    public isMenuOpen: boolean = false;
    ngOnInit() {
        this.store_data = JSON.parse(localStorage.getItem("LoggedInUser")!);
        this.masterPageComponent.menu_toggle(this.menu_open_f);

        this.router.events.pipe(
            filter(event => event instanceof NavigationEnd),
        ).subscribe(() => {
            const rt = this.getChild(this.activatedRoute);
            rt.data.subscribe((data: any) => {
                this.router_obj = data;
            });
            this.store_data = JSON.parse(localStorage.getItem("LoggedInUser")!);
        });
        if (!this.router_obj.title) {
            this.router_obj = this.appComponent.router_obj;
        }
        setTimeout(() => {
            this.store_data = JSON.parse(localStorage.getItem("LoggedInUser")!);
        }, 1000);
    }

    getChild(activatedRoute: ActivatedRoute): any {
        if (activatedRoute.firstChild) {
            return this.getChild(activatedRoute.firstChild);
        } else {
            return activatedRoute;
        }
    }

    logout() {
        this.MasterServices.removeObjservableUser();
        this.MasterServices.removeObjservableToken();
        this.router.navigate(['login']);
    }

    @ViewChild(MasterPageComponent, { static: false }) masterComponent !: MasterPageComponent;
    @ViewChildren(MatMenuTrigger) menuTriggers !: QueryList<MatMenuTrigger>;
    @HostListener('window:scroll')
    onWindowScroll() {
        this.menuTriggers.forEach((trigger: MatMenuTrigger) => {
            if (trigger && trigger.menuOpen) {
                trigger.closeMenu();
            }
        });
    }

}
