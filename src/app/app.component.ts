import { Component } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { filter } from 'rxjs/operators';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})
export class AppComponent {
    title = 'triranga-infra';

    constructor(
        private router: Router,
        private activatedRoute: ActivatedRoute,
        private titleService: Title
    ) { }

    public router_obj: any = {};

    ngOnInit() {
        this.router.events.pipe(
            filter(event => event instanceof NavigationEnd),
        ).subscribe(() => {
            const rt = this.getChild(this.activatedRoute);
            rt.data.subscribe((data : any) => {
                this.titleService.setTitle(data.title)
                this.router_obj = data;
            });

        });
    }

    getChild(activatedRoute: ActivatedRoute):any {
        if (activatedRoute.firstChild) {
            return this.getChild(activatedRoute.firstChild);
        } else {
            return activatedRoute;
        }
    }
}
