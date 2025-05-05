import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, Router } from '@angular/router';
import { Observable } from 'rxjs';
// import { CanActivate } from '@angular/router/src/utils/preactivation';
import { state } from '@angular/animations';

@Injectable({
    providedIn: 'root'
})

export class AuthGuard implements CanActivate {
    constructor(
        private routes: Router
    ) { }

    canActivate(
        next: ActivatedRouteSnapshot,
        state: RouterStateSnapshot): boolean {
        if (localStorage.getItem('LoggedInUser')) {
            return true;
        } else {
            this.routes.navigate(['login']);
            return false;
        }
    }


}

@Injectable({
    providedIn: 'root'
})
export class LoginAuthGuard implements CanActivate {
    constructor(
        private routes: Router
    ) { }

    canActivate(
        next: ActivatedRouteSnapshot,
        state: RouterStateSnapshot): boolean {
        if (!localStorage.getItem('LoggedInUser')) {
            return true;
        } else {
            this.routes.navigate(['/']);
            return false;
        }
    }


}