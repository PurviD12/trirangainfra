import { Component, OnInit, ViewChild, HostListener, ViewChildren, QueryList } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { MasterServicesService } from 'src/app/services/master-services.service';
// import { DaterangepickerConfig } from 'ng2-daterangepicker';
import * as moment from 'moment';
// import { MatDialog, PageEvent } from '@angular/material';
import { PageEvent } from '@angular/material/paginator';
import { ToastService } from 'src/app/services/toast.service';
import Swal from 'sweetalert2';
import { MatMenuTrigger } from '@angular/material/menu';
import * as dayjs from 'dayjs';

@Component({
    selector: 'app-team-list',
    templateUrl: './team-list.component.html',
    styleUrls: ['./team-list.component.css']
})
export class TeamListComponent implements OnInit {

    constructor(
        private http: HttpClient,
        public MasterService: MasterServicesService,
        // public daterangepickerOptions: DaterangepickerConfig,
        public router: Router,
        private ToastService: ToastService,
    ) {
        // this.daterangepickerOptions.settings = {
        //     autoUpdateInput: false,
        //     locale: { format: 'D MMM, YYYY' },
        //     alwaysShowCalendars: false,
        //     startDate: moment.unix(631135800),
        //     endDate: moment().subtract('days').endOf('day'),
        //     ranges: {
        //         'All': [moment.unix(631135800), moment().subtract('days').endOf('day')],
        //         'Today': [moment(), moment()],
        //         // 'Tomorrow': [moment().add(1, 'days').startOf('day'), moment().add(1, 'days').endOf('day')],
        //         'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        //         'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        //         'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        //         'This Month': [moment().startOf('month'), moment().endOf('month')],
        //         'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        //     }
        // };
        // const startDate = moment(this.daterangepickerOptions.settings.startDate).format('YYYY-MM-DD');
        // const endDate = moment(this.daterangepickerOptions.settings.endDate).format('YYYY-MM-DD');

        //   this.label_selected = "All";

        //   this.team_obj.from_date = "";
        //   this.team_obj.to_date = "";
        //   if (this.label_selected != "All") {
        //       this.team_obj.from_date = startDate;
        //       this.team_obj.to_date = endDate;
        //   }
        this.alwaysShowCalendars = true;

    }

    public label_selected: any = "";
    public selectedDateDisplay: any = "";

    decodeValue(encodedValue: string): string {
        return btoa(encodedValue);
    }

    public isLoading: boolean = false;
    public team_obj: any = {
        search: "",
        lead_type: "",
        page: 1,
        limit: 25,
    };
    public filter_check: any = {
        status_check: false
    };
    public team_list: any = [];
    public total_record: number = 0;

    public pageSizeOptions: number[] = [5, 10, 25, 100];

    // MatPaginator Output
    public pageEvent!: PageEvent;

    setPageSizeOptions(setPageSizeOptionsInput: string) {
        this.pageSizeOptions = setPageSizeOptionsInput.split(',').map(str => +str);
    }

    onPageChange(event: PageEvent) {
        this.team_obj.page = event.pageIndex + 1;
        this.team_obj.limit = event.pageSize;
        this.team_get();
    }

    ngOnInit() {
        this.team_get();

    }

    // public availablePages: number[] = [];
    public export: boolean = false;
    team_get() {
        if (this.export) {
            this.ToastService.info("Please wait... Data is exporting...");
            this.team_obj.export = 1;
        } else {
            this.isLoading = true;
            this.team_list = [];
        }

        const obj = this.team_obj;
        const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));
        this.team_obj.export = 0;
        this.MasterService.team_list_get(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (!this.export) {
                this.total_record = response.total_records;
                if (response.success) {
                    this.team_list = response.data;
                }
            } else {
                if (response.success) {
                    this.ToastService.success("Data exported.");
                    this.downloadFile(response.file_path);
                } else {
                    this.ToastService.error('Opps...something went wrong, Plesae try again.');
                }
            }

            this.isLoading = false;
            this.export = false;
        }, error => {
            this.export = false;
            this.isLoading = false;
        })
    }

    team_export() {
        this.export = true;
        this.team_get();
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

    //   onDateRangeSelected(selectedDates: any) {
    //       const startDate = moment(selectedDates.start).format('YYYY-MM-DD');
    //       const endDate = moment(selectedDates.end).format('YYYY-MM-DD');

    //       const startDate_display = moment(selectedDates.start).format('D MMM, YYYY');
    //       const endDate_display = moment(selectedDates.end).format('D MMM, YYYY');

    //       this.selectedDateDisplay = "";
    //       if (selectedDates.label != "All") {
    //           this.selectedDateDisplay = startDate_display + ' - ' + endDate_display;
    //       }
    //       this.label_selected = selectedDates.label;

    //       this.team_obj.from_date = "";
    //       this.team_obj.to_date = "";
    //       if (this.label_selected != "All") {
    //           this.team_obj.from_date = startDate;
    //           this.team_obj.to_date = endDate;
    //       }

    //       this.team_get();
    //   }

    //   clear_date_range() {
    //       this.selectedDateDisplay = "";
    //       this.team_obj.from_date = "";
    //       this.team_obj.to_date = "";
    //       this.team_get();
    //   }

    team_remove(master_user_id: any) {
        Swal.fire({
            title: 'Confirmation',
            text: 'Are you sure you want to delete?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
        }).then((result: any) => {
            if (result.isConfirmed) {
                const obj: { [key: string]: any } = {
                    master_user_id: master_user_id
                };
                const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

                this.MasterService.team_delete(mapped).subscribe(res => {
                    var response = JSON.parse(JSON.stringify(res));
                    if (response.success) {
                        this.team_get();
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

    status_change_click(master_user_id: any, index: any) {
        const obj: { [key: string]: any } = {
            master_user_id: master_user_id
        };
        const mapped = Object.keys(obj).map(key => ({ type: key, value: obj[key] }));

        this.MasterService.status_change(mapped).subscribe(res => {
            var response = JSON.parse(JSON.stringify(res));
            if (response.success) {
                this.team_list[index].is_active = response.is_active;
                this.ToastService.success(response.message);
            } else {
                this.ToastService.error(response.message);
            }
        }, error => {
        })
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






    selected: any = {
        startDate: moment.unix(631135800),
        endDate: moment().endOf('day'),
        label: 'All'
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

    ranges: any = {
        'All': [moment.unix(631135800), moment().subtract('days').endOf('day')],
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],

    }
    invalidDates: moment.Moment[] = [moment().add(2, 'days'), moment().add(3, 'days'), moment().add(5, 'days')];

    isInvalidDate = (m: moment.Moment) => {
        return this.invalidDates.some(d => d.isSame(m, 'day'))
    }



    onDateRangeSelected(selectedDates: { startDate: dayjs.Dayjs; endDate: dayjs.Dayjs; label: string }) {
        if (selectedDates && selectedDates.startDate && selectedDates.endDate) {
            const startDate = selectedDates.startDate.format('YYYY-MM-DD');
            const endDate = selectedDates.endDate.format('YYYY-MM-DD');

            const startDate_display = selectedDates.startDate.format('D MMM, YYYY');
            const endDate_display = selectedDates.endDate.format('D MMM, YYYY');

            this.selectedDateDisplay = "";
            if (selectedDates.label !== "All") {
                this.selectedDateDisplay = startDate_display + ' - ' + endDate_display;
            }

            this.label_selected = selectedDates.label;

            if (this.label_selected !== "All") {
                this.team_obj.from_date = startDate;
                this.team_obj.to_date = endDate;
            } else {
                this.team_obj.from_date = "";
                this.team_obj.to_date = "";
            }

            this.team_get();
        }
    }

    onDateRangeCleared() {
        this.selectedDateDisplay = "";
        this.team_obj.from_date = '';
        this.team_obj.to_date = '';
        // this.company_master_get(1);
        this.team_get();
    }
}

