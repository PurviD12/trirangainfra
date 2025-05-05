import { Component, OnInit, ViewChild, HostListener, ViewChildren, QueryList } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { MasterServicesService } from 'src/app/services/master-services.service';
// import { DaterangepickerConfig } from 'ng2-daterangepicker';
import * as moment from 'moment';
import { MatDialog } from '@angular/material/dialog';
import { MatMenuTrigger } from '@angular/material/menu';
import { PageEvent } from '@angular/material/paginator';
import { InquiryAddFollowUpComponent } from '../dialog/inquiry-add-follow-up/inquiry-add-follow-up.component';
import { InquiryViewFollowUpHistoryComponent } from '../dialog/inquiry-view-follow-up-history/inquiry-view-follow-up-history.component';
import { ToastService } from 'src/app/services/toast.service';
import { TransferLeadComponent } from '../dialog/transfer-lead/transfer-lead.component';
import { BigBuyerEmailComponent } from '../dialog/big-buyer-email/big-buyer-email.component';
import Swal from 'sweetalert2';
import * as dayjs from 'dayjs';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {


    constructor(
        private http: HttpClient,
        public MasterService: MasterServicesService,
        // public daterangepickerOptions: DaterangepickerConfig,
        private dialog: MatDialog,
        public router: Router,
        private ToastService: ToastService,
    ) {
        // this.range_picker();
        this.show_range_picker = true;

        this.alwaysShowCalendars = true;
    }

    public show_range_picker: boolean = false;
    // range_picker() {
    //     this.daterangepickerOptions.settings = {
    //         autoUpdateInput: false,
    //         locale: { format: 'D MMM, YYYY' },
    //         alwaysShowCalendars: false,
    //         startDate: moment().subtract(1, 'days'),
    //         endDate: moment(),
    //         ranges: {
    //             // 'All': [moment.unix(631135800), moment().subtract('days').endOf('day')],
    //             'All': [moment().subtract(1, 'days'), moment()],
    //             'Today': [moment(), moment()],
    //             // 'Tomorrow': [moment().add(1, 'days').startOf('day'), moment().add(1, 'days').endOf('day')],
    //             'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
    //             'Last 7 Days': [moment().subtract(6, 'days'), moment()],
    //             'Last 30 Days': [moment().subtract(29, 'days'), moment()],
    //             'This Month': [moment().startOf('month'), moment().endOf('month')],
    //             'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    //         }
    //     };
    //     const startDate = moment(this.daterangepickerOptions.settings.startDate).format('YYYY-MM-DD');
    //     const endDate = moment(this.daterangepickerOptions.settings.endDate).format('YYYY-MM-DD');

    //     this.label_selected = "All";

    //     this.inquiries_obj.from_date = "";
    //     this.inquiries_obj.to_date = "";
    //     if (this.label_selected != "All") {
    //         this.inquiries_obj.from_date = startDate;
    //         this.inquiries_obj.to_date = endDate;
    //     }
    // }

    public label_selected: any = "";
    public store_data: any = {};
    ngOnInit() {
        this.store_data = JSON.parse(localStorage.getItem("LoggedInUser")!);
        this.label_selected = "All";
        this.inquiries_get();
        this.status_get();
        this.plant_get();
        this.sales_get();
        this.state_get_for_filter();
        this.filter_applyed = false;
        this.selectedFinancialYear = this.getCurrentFinancialYear();
        this.financialyear = this.generateFinancialYears();
    }

    confirmationResult!: string;
    openConfirmationDialog(inquiry_data_pass: any) {
        const dialogRef = this.dialog.open(InquiryAddFollowUpComponent, {
            width: '550px',
            data: {
                status_list: this.status_list,
                remarks: "",
                status: inquiry_data_pass.status,
                inquiry_id: inquiry_data_pass.id,
            }
        });

        dialogRef.afterClosed().subscribe((result: any) => {
            if (result) {
                this.confirmationResult = 'Confirmed';
                this.inquiries_get();
            } else {
                this.confirmationResult = 'Cancelled';
            }
        });
    }

    transfer_lead(transfer_lead_id: any = "") {
        const transfer_lead_id_get = transfer_lead_id;
        if (transfer_lead_id_get) {
            this.transfer_obj.ids = [];
            this.transfer_obj.ids.push(transfer_lead_id_get);
        }
        const dialogRef = this.dialog.open(TransferLeadComponent, {
            width: '550px',
            data: {
                ids: this.transfer_obj.ids,
            }
        });

        dialogRef.afterClosed().subscribe((result: any) => {
            if (result) {
                this.inquiries_get();
            } else if (transfer_lead_id_get) {
                this.transfer_obj.ids = [];
            }
        });
    }

    confirmationHistory!: string;
    openHistoryDialog(inquiry_data_pass: any) {
        const dialogRef = this.dialog.open(InquiryViewFollowUpHistoryComponent, {
            width: '68%',
            // width: '1200px',
            position: {
                right: '0',
            },
            panelClass: 'dialog-slide-in',
            data: {
                inquiry_id: inquiry_data_pass.id,
            }
        });

        dialogRef.afterClosed().subscribe((result: any) => {
            if (result) {
                this.confirmationHistory = 'Confirmed';
                this.inquiries_get();
            } else {
                this.confirmationHistory = 'Cancelled';
            }
        });
    }


    openBigBuyerEmailDialog(inquiry_data_pass: any) {
        const dialogRef = this.dialog.open(BigBuyerEmailComponent, {
            width: '550px',
            data: {
                inquiry_id: inquiry_data_pass.id,
                email_address: inquiry_data_pass.email_address,
            }
        });

        dialogRef.afterClosed().subscribe((result: any) => {
            if (result) {
                // this.inquiries_get();
            }
        });
    }

    public selectedDateDisplay: any = "";
    public selectedDateDisplay_2: any = "";

    decodeValue(encodedValue: string): string {
        return btoa(encodedValue);
    }

    public isLoading: boolean = false;
    public inquiries_obj: any = {
        search: "",
        lead_type: "",
        page: 1,
        limit: 25,
        dashboard: this.router.url == "/dashboard" ? 1 : 0,
        filter: {
            status: [{}]
        },
        show_upcoming_followUp: 0,
    };
    public filter_check: any = {
        status_check: false,
        plant_check: false,
        priority_check: false,
        state_check: false,
    };
    public inquiry_list: any = [];
    public count_list: any = [];
    public total_record: number = 0;

    public pageSizeOptions: number[] = [5, 10, 25, 100];

    // MatPaginator Output
    public pageEvent!: PageEvent;

    setPageSizeOptions(setPageSizeOptionsInput: string) {
        this.pageSizeOptions = setPageSizeOptionsInput.split(',').map(str => +str);
    }

    onPageChange(event: PageEvent) {
        this.inquiries_obj.page = event.pageIndex + 1;
        this.inquiries_obj.limit = event.pageSize;
        this.inquiries_get();
    }

    public filter_applyed: boolean = false;
    // public availablePages: number[] = [];
    public export: boolean = false;
    inquiries_get(filter_apply = '') {
        if (filter_apply == 'filter_apply') {
            this.inquiries_obj.page = 1;
            if (this.pageEvent) {
                this.pageEvent.pageIndex = 0;
            }
        }
        this.inquiry_assign_f = false;
        this.transfer_obj.ids = [];
        if (this.export) {
            this.ToastService.info("Please wait... Data is exporting...");
            this.inquiries_obj.export = 1;
        } else {
            this.isLoading = true;
            this.inquiry_list = [];
        }

        this.filter_check.status_check = false;
        this.inquiries_obj.filter.status = [];
        this.status_list.forEach((value: any, key: any) => {
            if (value.checked) {
                this.inquiries_obj.filter.status.push(value.name);
                this.filter_check.status_check = true;
            }
        });

        this.filter_check.priority_check = false;
        this.inquiries_obj.filter.priority = [];
        this.priority_list.forEach((value: any, key: any) => {
            if (value.checked) {
                this.inquiries_obj.filter.priority.push(value.name);
                this.filter_check.priority_check = true;
            }
        });

        this.filter_check.plant_check = false;
        this.inquiries_obj.filter.which_plant_city_ids = [];
        this.plant_list.forEach((value: any, key: any) => {
            if (value.checked) {
                this.inquiries_obj.filter.which_plant_city_ids.push(value.city_id);
                this.filter_check.plant_check = true;
            }
        });

        this.filter_check.sales_check = false;
        this.inquiries_obj.filter.sales_master_user_ids = [];
        this.sales_list.forEach((value: any, key: any) => {
            if (value.checked) {
                this.inquiries_obj.filter.sales_master_user_ids.push(value.master_user_id);
                this.filter_check.sales_check = true;
            }
        });

        this.filter_check.state_check = false;
        this.inquiries_obj.filter.state_ids = [];
        this.state_list_for_filter.forEach((value: any, key: any) => {
            if (value.checked) {
                this.inquiries_obj.filter.state_ids.push(value.state_id);
                this.filter_check.state_check = true;
            }
        });

        this.filter_applyed = false;
        if (this.filter_check.status_check || this.filter_check.priority_check || this.filter_check.plant_check || this.filter_check.sales_check || this.filter_check.state_check || this.inquiries_obj.search != "" || this.inquiries_obj.from_date_followUp || this.inquiries_obj.to_date_followUp || this.inquiries_obj.from_date || this.inquiries_obj.to_date) {
            this.filter_applyed = true;
        }

        this.inquiries_obj.show_upcoming_followUp = 0;
        if (this.show_upcoming_followUp_dis) {
            this.inquiries_obj.show_upcoming_followUp = 1;
        }
        const obj = this.inquiries_obj;
        const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));
        this.inquiries_obj.export = 0;
        this.MasterService.inquiries_list(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (!this.export) {
                this.total_record = response.total_records;
                if (response.success) {
                    this.inquiry_list = response.data;
                }
                this.count_list = response.total_records_list;
                if (!this.inquiries_obj.lead_type && this.router.url == "/dashboard") {
                    this.inquiries_obj.lead_type = this.count_list[0].lead_type;
                }
                if (filter_apply == 'filter_apply') {
                    if (this.pageEvent) {
                        this.pageEvent.length = this.total_record;
                    }
                }
            } else {
                if (response.success) {
                    this.ToastService.success("Data exported.");
                    this.downloadFile(response.file_path);
                } else {
                    this.ToastService.error('Opps...something went wrong, Plesae try again.');
                }
            }

            // var totalPages = Math.ceil(this.total_record / this.inquiries_obj.limit);
            // this.availablePages = [];
            // for (let i = 0; i < totalPages; i++) {
            //     this.availablePages.push(i);
            // }
            // console.log(this.availablePages);

            this.isLoading = false;
            this.export = false;
        }, error => {
            this.export = false;
            this.isLoading = false;
        })
    }

    inquiry_export() {
        this.export = true;
        this.inquiries_get();
    }

    clear_all_filter() {
        this.status_filter_clear("all_clear");
        this.priority_filter_clear("all_clear");
        this.plant_filter_clear("all_clear");
        this.sales_filter_clear("all_clear");
        this.state_filter_clear("all_clear");
        this.inquiries_obj.search = "";
        if (this.router.url == '/dashboard' && this.inquiries_obj.lead_type == "upcoming_followUp_lead") {
            const temp_store = this.inquiries_obj.lead_type;
            this.inquiries_obj.lead_type = "";
            setTimeout(() => {
                this.inquiries_obj.lead_type = temp_store;
            }, 1);
            this.lead_type_change(this.inquiries_obj.lead_type, "all_clear");
        } else {
            this.clear_date_range("all_clear");
            this.show_range_picker = false;
            setTimeout(() => {
                this.show_range_picker = true;
            }, 1);
            // this.range_picker();
        }
        this.inquiries_get();
    }

    downloadFile(url: any) {
        this.http.get(url, { responseType: 'blob' })
            .subscribe((blob: Blob) => {
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = url.substring(url.lastIndexOf('/') + 1);
                link.click();
            });
    }

    public priority_list: any = [
        {
            name: "Hot"
        },
        {
            name: "Cold"
        },
        {
            name: "Warm"
        }
    ];

    priority_filter_clear(all_clear = "") {
        this.priority_list.forEach((value: any, key: any) => {
            value.checked = false;
        });
        this.filter_check.priority_check = false;
        if (all_clear == "") {
            this.inquiries_get();
        }
    }

    public status_obj: any = {};
    public status_list: any = [];
    public isLoadingStatus: boolean = false;
    status_get() {
        this.isLoadingStatus = true;
        this.status_list = [];

        const obj = this.status_obj;
        const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

        this.MasterService.status_list_get(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.status_list = response.data;
            }
            this.isLoadingStatus = false;
        }, error => {
            this.isLoadingStatus = false;
        })
    }

    status_filter_clear(all_clear = "") {
        this.status_list.forEach((value: any, key: any) => {
            value.checked = false;
        });
        this.filter_check.status_check = false;
        if (all_clear == "") {
            this.inquiries_get();
        }
    }

    lead_remove(id: any) {
        Swal.fire({
            title: 'Confirmation',
            text: 'Are you sure you want to delete?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                const obj = {
                    id: id
                };
                const mapped = Object.keys(obj).map(key => ({
                    type: key,
                    value: (obj as { [key: string]: any })[key]
                }));


                this.MasterService.lead_delete(mapped).subscribe(res => {
                    var response = JSON.parse(JSON.stringify(res));
                    if (response.success) {
                        this.inquiries_get();
                        Swal.fire(
                            'Deleted!',
                            response.message,
                            'success'
                        )
                    } else {
                        this.ToastService.error(response.message);
                    }
                }, error => {
                })
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire(
                    'Cancelled',
                    'Your data is safe :)',
                    'error'
                )
            }
        });
    }

    public show_upcoming_followUp_dis: boolean = false;
    upcoming_filter_clear(all_clear = "") {
        this.show_upcoming_followUp_dis = false;
        this.inquiries_obj.show_upcoming_followUp = 0;
        if (all_clear == "") {
            this.inquiries_get();
        }
    }

    lead_type_change(type = "", all_clear = "") {
        if (!this.isLoading) {
            // if (type == "today" || type == "today") {
            //     const startDateDefault = moment().startOf('day').format('YYYY-MM-DD');
            //     const endDateDefault = moment().endOf('day').format('YYYY-MM-DD');

            //     const startDateDisplay = moment().startOf('day').format('D MMM, YYYY');
            //     const endDateDisplay = moment().endOf('day').format('D MMM, YYYY');

            //     this.selectedDateDisplay = startDateDisplay + ' - ' + endDateDisplay;

            //     this.inquiries_obj.from_date = startDateDefault;
            //     this.inquiries_obj.to_date = endDateDefault;
            // }
            // this.daterangepickerOptions.settings = {
            //     autoUpdateInput: false,
            //     locale: { format: 'D MMM, YYYY' },
            //     alwaysShowCalendars: false,
            //     startDate: moment().subtract(1, 'days'),
            //     endDate: moment(),
            //     ranges: {
            //         // 'All': [moment.unix(631135800), moment().subtract('days').endOf('day')],
            //         'All': [moment().subtract(1, 'days'), moment()],
            //         'Tomorrow': [moment().add(1, 'days').startOf('day'), moment().add(1, 'days').endOf('day')],
            //         'In 7 Days': [moment().add(1, 'days').startOf('day'), moment().add(7, 'days')],
            //         'In 30 Days': [moment().add(1, 'days').startOf('day'), moment().add(30, 'days')],
            //         'This Month': [moment().startOf('month'), moment().endOf('month')],
            //     }
            // };
            this.inquiries_obj.lead_type = type;
            this.clear_date_range_2();
            if (all_clear == "") {
                this.inquiries_get();
            }
        }
    }

    // onDateRangeSelected(selectedDates: any) {
    //     const startDate = moment(selectedDates.start).format('YYYY-MM-DD');
    //     const endDate = moment(selectedDates.end).format('YYYY-MM-DD');

    //     const startDate_display = moment(selectedDates.start).format('D MMM, YYYY');
    //     const endDate_display = moment(selectedDates.end).format('D MMM, YYYY');

    //     this.selectedDateDisplay = "";
    //     if (selectedDates.label != "All") {
    //         this.selectedDateDisplay = startDate_display + ' - ' + endDate_display;
    //     }
    //     this.label_selected = selectedDates.label;

    //     this.inquiries_obj.from_date = "";
    //     this.inquiries_obj.to_date = "";
    //     if (this.label_selected != "All") {
    //         this.inquiries_obj.from_date = startDate;
    //         this.inquiries_obj.to_date = endDate;
    //     }
    //     this.inquiries_get();
    // }

    clear_date_range(all_clear = "") {
        this.selectedDateDisplay = "";
        this.inquiries_obj.from_date = "";
        this.inquiries_obj.to_date = "";
        if (all_clear == "") {
            this.inquiries_get();
        }
    }

    onDateRangeSelected_2(selectedDates: { startDate: any; endDate: any; label: string }) {
        if (selectedDates && selectedDates.startDate && selectedDates.endDate && selectedDates.label != undefined) {
            const startDate = selectedDates.startDate.format('YYYY-MM-DD');
            const endDate = selectedDates.endDate.format('YYYY-MM-DD');

            const startDate_display = selectedDates.startDate.format('D MMM, YYYY');
            const endDate_display = selectedDates.endDate.format('D MMM, YYYY');

            this.selectedDateDisplay_2 = "";

            if (selectedDates.label != undefined && selectedDates.label != "All") {
                this.selectedDateDisplay_2 = startDate_display + ' - ' + endDate_display;
            }

            this.label_selected = selectedDates.label;

            this.inquiries_obj.from_date_followUp = "";
            this.inquiries_obj.to_date_followUp = "";
            if (selectedDates.label != undefined && this.label_selected != "All") {
                this.inquiries_obj.from_date_followUp = startDate;
                this.inquiries_obj.to_date_followUp = endDate;
            }

            this.inquiries_get();
        }
    }

    clear_date_range_2(click_check = "") {
        this.selected_2 = {
            startDate: moment().subtract(1, 'days'),
            endDate: moment(),
        };
        this.selectedDateDisplay_2 = "";
        this.inquiries_obj.from_date_followUp = "";
        this.inquiries_obj.to_date_followUp = "";
        if (click_check) {
            this.inquiries_get();
        }
    }

    @ViewChildren(MatMenuTrigger) menuTriggers!: QueryList<MatMenuTrigger>;
    @HostListener('window:scroll')
    onWindowScroll() {
        this.menuTriggers.forEach((trigger: MatMenuTrigger) => {
            if (trigger && trigger.menuOpen) {
                trigger.closeMenu();
            }
        });
    }

    public inquiry_assign_f: boolean = false;
    public transfer_obj: any = {
        ids: []
    };
    inquiry_assign() {
        this.check_all_check = false;
        this.transfer_obj.ids = [];
        this.inquiry_list.forEach((value: any, key: any) => {
            value.checked = false;
        });
        if (this.inquiry_assign_f) {
            this.inquiry_assign_f = false;
        } else {
            this.inquiry_assign_f = true;
        }
    }

    public check_all_check: boolean = false;
    check_all_inquiries() {
        setTimeout(() => {
            this.check_all_check = true;
        }, 1);
        var over_transfer = false;
        this.inquiry_list.forEach((value: any, key: any) => {
            if (this.transfer_obj.ids.length != 50) {
                value.checked = true;
                if (this.transfer_obj.ids.indexOf(value.id) == -1) {
                    this.transfer_obj.ids.push(value.id);
                }
            } else {
                over_transfer = true;
            }
        });
        if (over_transfer) {
            over_transfer = false;
            this.ToastService.error('You can select maximum 50 inquiries at one time.');
        }
    }

    check_one_inquiry(data: any) {
        if (this.transfer_obj.ids.indexOf(data.id) != -1) {
            this.transfer_obj.ids.splice(this.transfer_obj.ids.indexOf(data.id), 1);
        } else if (this.transfer_obj.ids.length != 50) {
            this.transfer_obj.ids.push(data.id);
        } else {
            setTimeout(() => {
                data.checked = false;
            }, 1);
            this.ToastService.error('You can select maximum 50 inquiries at one time.');
        }
        console.log(this.transfer_obj);
    }


    public isLoadingPlant: boolean = false;
    public plant_list: any = [];
    plant_get() {
        this.isLoadingPlant = true;
        this.plant_list = [];

        const obj = {
            no_limit: 1
        };
        const mapped = Object.keys(obj).map(key => ({
            type: key,
            value: (obj as { [key: string]: any })[key]
        }));


        this.MasterService.plants(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.plant_list = response.data;
            }
            this.isLoadingPlant = false;
        }, error => {
            this.isLoadingPlant = false;
        })
    }

    plant_filter_clear(all_clear = "") {
        this.plant_list.forEach((value: any, key: any) => {
            value.checked = false;
        });
        this.filter_check.plant_check = false;
        if (all_clear == "") {
            this.inquiries_get();
        }
    }


    public isLoadingSales: boolean = false;
    public sales_list: any = [];
    sales_get() {
        this.isLoadingSales = true;
        this.sales_list = [];

        const obj = {
            no_limit: 1
        };
        const mapped = Object.keys(obj).map(key => ({
            type: key,
            value: (obj as { [key: string]: any })[key]
        }));


        this.MasterService.team_list_get(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.sales_list = response.data;
            }
            this.isLoadingSales = false;
        }, error => {
            this.isLoadingSales = false;
        })
    }

    sales_filter_clear(all_clear = "") {
        this.sales_list.forEach((value: any, key: any) => {
            value.checked = false;
        });
        this.filter_check.sales_check = false;
        if (all_clear == "") {
            this.inquiries_get();
        }
    }


    public state_list_for_filter: any = [];
    state_get_for_filter() {
        this.state_list_for_filter = [];
        const obj = {};
        const mapped = Object.keys(obj).map(key => ({
            type: key,
            value: (obj as { [key: string]: any })[key]
        }));


        this.MasterService.states_list_for_filter(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.state_list_for_filter = response.data;
            }
        }, error => {
        })
    }

    state_filter_clear(all_clear = "") {
        this.state_list_for_filter.forEach((value: any, key: any) => {
            value.checked = false;
        });
        this.filter_check.state_check = false;
        if (all_clear == "") {
            this.inquiries_get();
        }
    }


    selected: any = {
        // startDate: moment.unix(631135800),
        // endDate: moment().endOf('day'),
        startDate: moment().subtract(1, 'days'),
        endDate: moment(),
        // label: 'All'
    };
    selected_2: any = {
        // startDate: moment.unix(631135800),
        // endDate: moment().endOf('day'),
        startDate: moment().subtract(1, 'days'),
        endDate: moment(),
        // label: 'All'
    };
    alwaysShowCalendars: boolean;

    private financialYearRange = this.getFinancialYearRange();

    private getFinancialYearRange(): { start: moment.Moment; end: moment.Moment } {
        const currentMonth = moment().month();
        let startOfFinancialYear, endOfFinancialYear;

        if (currentMonth >= 3) {
            startOfFinancialYear = moment().startOf('year').month(3).startOf('month');
            endOfFinancialYear = moment().startOf('year').add(1, 'year').month(2).endOf('month');
        } else {
            startOfFinancialYear = moment().startOf('year').subtract(1, 'year').month(3).startOf('month');
            endOfFinancialYear = moment().startOf('year').month(2).endOf('month');
        }

        return { start: startOfFinancialYear, end: endOfFinancialYear };
    }

    ranges1: any = {
        // 'All': [moment.unix(631135800), moment().subtract('days').endOf('day')],
        'All': [moment().subtract(1, 'days'), moment()],
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        // 'financialYearRange': [moment().startOf('month'), moment().endOf('month')],
    }
    ranges2: any = {
        // 'All': [moment.unix(631135800), moment().endOf('day')],
        'All': [moment().subtract(1, 'days'), moment()],
        'Tomorrow': [moment().add(1, 'days').startOf('day'), moment().add(1, 'days').endOf('day')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],

    }
    //  this.label_selected = "All";
    invalidDates: moment.Moment[] = [moment().add(2, 'days'), moment().add(3, 'days'), moment().add(5, 'days')];

    isInvalidDate = (m: moment.Moment) => {
        return this.invalidDates.some(d => d.isSame(m, 'day'))
    }



    onDateRangeSelected(selectedDates: { startDate: any; endDate: any; label: string }) {
        if (selectedDates && selectedDates.startDate && selectedDates.endDate && selectedDates.label != undefined) {
            const startDate = selectedDates.startDate.format('YYYY-MM-DD');
            const endDate = selectedDates.endDate.format('YYYY-MM-DD');

            const startDate_display = selectedDates.startDate.format('D MMM, YYYY');
            const endDate_display = selectedDates.endDate.format('D MMM, YYYY');

            this.selectedDateDisplay = "";

            if (selectedDates.label != undefined && selectedDates.label != "All") {
                this.selectedDateDisplay = startDate_display + ' - ' + endDate_display;
            }

            this.label_selected = selectedDates.label;

            this.inquiries_obj.from_date = "";
            this.inquiries_obj.to_date = "";
            if (selectedDates.label != undefined && this.label_selected != "All") {
                this.inquiries_obj.from_date = startDate;
                this.inquiries_obj.to_date = endDate;
            }

            this.inquiries_get();
        }
    }

    onDateRangeCleared() {
        this.selectedDateDisplay = "";
        this.inquiries_obj.from_date = '';
        this.inquiries_obj.to_date = '';
        // this.company_master_get(1);
        this.inquiries_get();
    }

    // Financial Year

    financialyear: { value: string; viewValue: string }[] = [];

    selectedFinancialYear: string = '';

    getCurrentFinancialYear(): string {
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth() + 1;
        if (month >= 4) {
            return `${year}-${year + 1}`;
        } else {
            return `${year - 1}-${year}`;
        }
    }
    generateFinancialYears(): { value: string; viewValue: string }[] {
        const years: { value: string; viewValue: string }[] = [];
        const startYear = 2022;
        const currentFY = this.getCurrentFinancialYear();
        const currentStartYear = parseInt(currentFY.split('-')[0]);
    
        for (let year = currentStartYear; year >= startYear; year--) {
            const fy = `${year}-${year + 1}`;
            years.push({ value: fy, viewValue: fy });
        }
    
        return years;
    }
    

    trackyear(index: number, financial: any): any {
        return financial.value;
    }

    onFinancialYearChange(selected: string) {
        console.log('Financial year changed to:', selected);

    }
}