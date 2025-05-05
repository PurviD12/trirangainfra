import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { LoginComponent } from './pages/login/login.component';
import { AuthGuard, LoginAuthGuard } from './auth_guard/auth-guard';
import { MasterPageComponent } from './pages/master-page/master-page.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { TeamListComponent } from './team/team-list/team-list.component';
import { TeamAddComponent } from './team/team-add/team-add.component';
import { ImportLeadsComponent } from './pages/import-leads/import-leads.component';
import { ProfileUpdateComponent } from './pages/profile/profile-update/profile-update.component';
import { AddComponent } from './pages/inquiry/add/add.component';
import { InquiriesComponent } from './pages/inquiries/inquiries.component';


const routes: Routes = [
  { path: 'login', component: LoginComponent, data: { title: "Login" }, canActivate: [LoginAuthGuard] },
  {
    path: "", component: MasterPageComponent, canActivate: [AuthGuard],
    children: [
      { path: 'dashboard', component: DashboardComponent, data: { title: "Dashboard" } },
      { path: 'inquiries', component: InquiriesComponent, data: { title: "All Leads" } },
      { path: 'profile/update', component: ProfileUpdateComponent, data: { title: "Profile Update" } },
      { path: 'profile/password/update', component: ProfileUpdateComponent, data: { title: "Profile Update" } },
      { path: 'inquiry/add', component: AddComponent, data: { title: "Inquiry Add", flag: "inquiry" } },
      { path: 'inquiry/update/:id', component: AddComponent, data: { title: "Inquiry Update", flag: "inquiry" } },
      { path: 'team', component: TeamListComponent, data: { title: "Team List", flag: "team", disabled_sub_user: 1 } },
      { path: 'team/add', component: TeamAddComponent, data: { title: "Team Add", flag: "team", disabled_sub_user: 1 } },
      { path: 'team/update/:master_user_id', component: TeamAddComponent, data: { title: "Team Update", flag: "team", disabled_sub_user: 1 } },
      { path: 'team/change-password/:master_user_id', component: TeamAddComponent, data: { title: "Change Password", flag: "team", disabled_sub_user: 1, change_password: 1 } },
      { path: 'import-leads', component: ImportLeadsComponent, data: { title: "Import Leads", flag: "import_leads", disabled_sub_user: 1 } },
    ]
  }
];

@NgModule({
  imports: [RouterModule.forRoot(routes, { scrollPositionRestoration: 'enabled' })],
  exports: [RouterModule]
})
export class AppRoutingModule { }
