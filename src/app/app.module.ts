import { Directive, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { OnlyNumberDirective } from './only-number.directive';
import { ToastrModule } from 'ngx-toastr';
import { FormControl, FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MAT_DATE_FORMATS, MatRippleModule } from '@angular/material/core';
import { MatMenuModule } from '@angular/material/menu';
import { MatIconModule } from '@angular/material/icon';
import { MatDividerModule } from '@angular/material/divider';
import { MatButtonModule } from '@angular/material/button';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { NgxMatSelectSearchModule } from 'ngx-mat-select-search';
import { NgxPrintModule } from 'ngx-print';
import { OnlyNumberRateDirective } from './only-number-rate.directive';
import { MatDatepicker, MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatRadioModule } from '@angular/material/radio';
import { MatMomentDateModule } from '@angular/material-moment-adapter';
import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { MatBottomSheetModule } from '@angular/material/bottom-sheet';
import { NgxColorsModule } from 'ngx-colors';
import { MatChipsModule } from '@angular/material/chips';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatTabsModule } from '@angular/material/tabs';
import { NgxEditorModule } from 'ngx-editor';
import { OnlyAlphaDirective } from './only-alpha.directive';
import { LoginComponent } from './pages/login/login.component';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { NgxMatDatetimePickerModule, NgxMatTimepickerModule, NgxMatNativeDateModule } from '@angular-material-components/datetime-picker';
import { NgxDaterangepickerMd } from 'ngx-daterangepicker-material';
import { SidebarComponent } from './pages/sidebar/sidebar.component';
import { MasterPageComponent } from './pages/master-page/master-page.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { TransferLeadComponent } from './pages/dialog/transfer-lead/transfer-lead.component';
import { BigBuyerEmailComponent } from './pages/dialog/big-buyer-email/big-buyer-email.component';
import { InquiryAddAttachmentComponent } from './pages/dialog/inquiry-add-attachment/inquiry-add-attachment.component';
import { InquiryViewFollowUpHistoryComponent } from './pages/dialog/inquiry-view-follow-up-history/inquiry-view-follow-up-history.component';
import { InquiryAddFollowUpComponent } from './pages/dialog/inquiry-add-follow-up/inquiry-add-follow-up.component';
import { TeamAddComponent } from './team/team-add/team-add.component';
import { TeamListComponent } from './team/team-list/team-list.component';
import { ImportLeadsComponent } from './pages/import-leads/import-leads.component';
import { ProfileUpdateComponent } from './pages/profile/profile-update/profile-update.component';
import { InquiriesComponent } from './pages/inquiries/inquiries.component';
import { AddComponent } from './pages/inquiry/add/add.component';
import { MatPaginatorModule } from '@angular/material/paginator';






export const MY_DATE_FORMATS = {
    parse: {
        dateInput: ['yyyy-MM-dd'],
    },
    display: {
        dateInput: 'DD MMM, YYYY',
        monthYearLabel: 'MMM yyyy',
        dateA11yLabel: 'LL',
        monthYearA11yLabel: 'MMMM yyyy',
    },
};

export const MY_FORMATS_MONTH_YEAR = {
    parse: {
        dateInput: 'MM/YYYY',
    },
    display: {
        dateInput: 'MM/YYYY',
        monthYearLabel: 'MMM YYYY',
        dateA11yLabel: 'LL',
        monthYearA11yLabel: 'MMMM YYYY',
    },
};
@NgModule({
    declarations: [
        AppComponent,
        OnlyAlphaDirective,
        OnlyNumberDirective,
        OnlyNumberRateDirective,
        LoginComponent,
        SidebarComponent,
        MasterPageComponent,
        DashboardComponent,
        TransferLeadComponent,
        BigBuyerEmailComponent,
        InquiryAddAttachmentComponent,
        InquiryViewFollowUpHistoryComponent,
        InquiryAddFollowUpComponent,
        TeamAddComponent,
        TeamListComponent,
        ImportLeadsComponent,
        ProfileUpdateComponent,
        InquiriesComponent,
        AddComponent,
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        BrowserAnimationsModule,
        FormsModule,
        HttpClientModule,
        ReactiveFormsModule,
        MatProgressSpinnerModule,
        MatRippleModule,
        MatMenuModule,
        MatButtonModule,
        MatDividerModule,
        MatIconModule,
        MatInputModule,
        MatTooltipModule,
        MatInputModule,
        MatPaginatorModule,
        MatFormFieldModule,
        MatSelectModule,
        NgxMatSelectSearchModule,
        NgxPrintModule,
        MatDialogModule,
        MatDatepickerModule,
        MatNativeDateModule,
        MatMomentDateModule,
        MatCheckboxModule,
        MatRadioModule,
        MatBottomSheetModule,
        NgxColorsModule,
        MatChipsModule,
        MatSnackBarModule,
        MatTabsModule,
        MatAutocompleteModule,
        NgxMatDatetimePickerModule,
        NgxMatTimepickerModule,
        NgxMatNativeDateModule,
        NgxEditorModule,
        ToastrModule.forRoot({
            positionClass: 'toast-bottom-right',
        }),
        NgxDaterangepickerMd.forRoot()
    ],
    providers: [
        { provide: MatDialogRef, useValue: {} },
        { provide: MAT_DIALOG_DATA, useValue: [] },

        { provide: MAT_DATE_FORMATS, useValue: MY_DATE_FORMATS }
    ],
    bootstrap: [AppComponent],
    schemas: [CUSTOM_ELEMENTS_SCHEMA]
})
export class AppModule { }
