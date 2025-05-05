import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable, Subject } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
    providedIn: 'root'
})
export class MasterServicesService {

    constructor(
        private http: HttpClient,
    ) { }

    //Setting observable user for session
    private observableObj = new Subject<any>();
    setObjservableUser(objLoggedInUser: any) {
        this.observableObj.next(objLoggedInUser);
        localStorage.setItem("LoggedInUser", objLoggedInUser);
    }


    // Unset Observable variable
    removeObjservableUser() {
        this.observableObj.next("");
        localStorage.removeItem("LoggedInUser");
    }

    // Getting data from getObservable user     
    geObjservableUser(): Observable<any> {
        return this.observableObj.asObservable();
    }

    private observableObj_token = new Subject<any>();
    setObjservableToken(token: any) {
        this.observableObj_token.next(token);
        localStorage.setItem("token", token);
    }
    removeObjservableToken() {
        this.observableObj_token.next("");
        localStorage.removeItem("token");
    }
    geObjservableToken(): Observable<any> {
        return this.observableObj_token.asObservable();
    }

    admin_details_get(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'master_users/details';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        if (data) {
            data.forEach((element: any) => {
                body = body.append(element.type, element.value);
            });
        }

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    admin_details_check_get(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'master_users/check_details';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        if (data) {
            data.forEach((element: any) => {
                body = body.append(element.type, element.value);
            });
        }

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    login_form(data: any): Observable<any> {
        const url = environment.api_url + 'admin_login/login';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'User QRNLQNSADDWLWZUSOSGQIARWBRVBWQJZEBKIGAOHAIBXIUEFDZPEBUWDW2004486403',
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    inquiry_save(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/save';
        // let body = new HttpParams();
        // body = body.append('call_app', "true");
        // body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        // data.forEach((element : any) => {
        //     body = body.append(element.type, element.value);
        // });

        const formData: FormData = new FormData();
        formData.append('call_app', "true");
        formData.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);

        data.forEach((element: any) => {
            if (element.type == "attachment_array") {
                element.value.forEach((value: any, key: any) => {
                    Object.entries(value).forEach((value_2: any, key_2: any) => {
                        formData.append(element.type + "[" + key + "][" + value_2[0] + "]", value_2[1]);
                    });
                });
            } else {
                formData.append(element.type, element.value);
            }
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, formData, { headers: httpHeaders });
    }

    addFollowUp_save(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/add_followUp';

        const formData: FormData = new FormData();
        formData.append('call_app', "true");
        formData.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            formData.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, formData, { headers: httpHeaders });
    }

    lead_delete(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/delete';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    status_list_get(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/status_list';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        if (data) {
            data.forEach((element: any) => {
                body = body.append(element.type, element.value);
            });
        }

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    inquiries_list(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/list';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            if (element.type == "filter") {
                body = body.append(element.type, JSON.stringify(element.value));
            } else {
                body = body.append(element.type, element.value);
            }
            // console.log(element.type.filter.status);
            // element.filter.forEach(element_2 => {
            //     body = body.append(element_2.type, element_2.value);
            // });
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    followUp_history(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/followUp_history';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    states(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'state/list';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    states_list_for_filter(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'state/list_filter';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    cities(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'city/list';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    plants(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/plant_get';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    /* Team Services START */
    team_save(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/save';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    team_password(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/change_password';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    team_delete(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/delete';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    status_change(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/status_change';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    team_list_get(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/list';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    team_assign_get(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/list_get_assign_user';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    lead_assign(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'team/assign_lead_to_sales';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }
    /* Team Services END */

    /* Profile Update All Users START */
    update_profile_all_users_set(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'master_users/update_profile_all_users';
        // let body = new HttpParams();
        // body = body.append('call_app', "true");
        // body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        // data.forEach((element : any) => {
        //     body = body.append(element.type, element.value);
        // });

        const formData: FormData = new FormData();
        formData.append('call_app', "true");
        formData.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            formData.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, formData, { headers: httpHeaders });
    }
    /* Profile Update All Users END */

    /* Big Buyer Email START */
    big_buyer_email_send(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/add_followUp_email';
        const formData: FormData = new FormData();
        formData.append('call_app', "true");
        formData.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            formData.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, formData, { headers: httpHeaders });
    }
    /* Big Buyer Email END */

    master_user_password(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'master_users/change_password';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }

    /* Import Leads START */
    import_save(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'inquiries/import_leads';
        const formData: FormData = new FormData();
        formData.append('call_app', "true");
        formData.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            formData.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, formData, { headers: httpHeaders });
    }
    /* Import Leads END */

    /* Source START */
    source_list(data: any): Observable<any> {
        const LoggedInUser_obj = JSON.parse(localStorage.getItem('LoggedInUser')!);
        const LoggedInUser_token = localStorage.getItem('token');

        const url = environment.api_url + 'source/list';
        let body = new HttpParams();
        body = body.append('call_app', "true");
        body = body.append("logged_in_master_user_id", LoggedInUser_obj.master_user_id);
        data.forEach((element: any) => {
            body = body.append(element.type, element.value);
        });

        let httpHeaders = new HttpHeaders({
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + LoggedInUser_token,
        });
        return this.http.post(url, body, { headers: httpHeaders });
    }
    /* Source END */
}