import { Injectable } from '@angular/core';
import { ToastrService } from 'ngx-toastr';

@Injectable({
    providedIn: 'root'
})
export class ToastService {

    constructor(
        private toastr: ToastrService
    ) { }

    success(data: any){
        this.toastr.success(data);
    }

    error(data: any){
        this.toastr.error(data);
    }

    show(data: any){
        this.toastr.show(data);
    }

    info(data: any){
        this.toastr.info(data);
    }

    warning(data: any){
        this.toastr.warning(data);
    }
}
