import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import {HomeComponent} from "./home/home.component";
import {IndexComponent} from "./index/index.component";
import {DetailComponent} from "./detail/detail.component";
import {ProjectListComponent} from "./project/project-list/project-list.component";
import {ManageListComponent} from "./manage/manage-list/manage-list.component";
import {TopologyComponent} from "./instance/topology/topology.component";
import {WebShellComponent} from "./shell/web-shell/web-shell.component";
import {LoginComponent} from "./login/login.component";
import {ProjectCreateComponent} from "./project/project-create/project-create.component";
import {ComposeListComponent} from "./compose/compose-list/compose-list.component";
import {InstanceComponent} from "./instance/instance/instance.component";
import {EnvComponent} from "./env/env.component";
import {ContainerListComponent} from "./container/container-list/container-list.component";
import {ScriptComponent} from "./task/script/script.component";
import {ScriptCreateComponent} from "./task/script-create/script-create.component";
import {CustomerGroupComponent} from "./task/customer-group/customer-group.component";
import {CreateCustomerGroupComponent} from "./task/create-customer-group/create-customer-group.component";
import {TaskComponent} from "./task/task/task.component";
import {TaskCreateComponent} from "./task/task-create/task-create.component";
import {CronJobsComponent} from "./task/cron-jobs/cron-jobs.component";
import {CronJobsCreateComponent} from "./task/cron-jobs-create/cron-jobs-create.component";
import {SettingComponent} from "./manage/setting/setting.component";
import {LogComponent} from "./log/log/log.component";
import {LogDetailComponent} from "./log/log-detail/log-detail.component";
import {AccountListComponent} from "./manage/account-list/account-list.component";

const routes: Routes = [
  {
    path: '', redirectTo: 'home', pathMatch: 'full'
  },
  {
    path: 'home',
    component: HomeComponent,
    children: [
      { path: '', redirectTo: 'index', pathMatch: 'full'},
      {
        path: 'index',
        component: IndexComponent,
        children: [
          {path: '', redirectTo: 'projectList', pathMatch: 'full'},
          {path: 'setting', component: SettingComponent},
          {path: 'account', component: AccountListComponent},
          {path: 'projectList', component: ProjectListComponent},
          {path: 'manage', component: ProjectListComponent},
          {path: 'perm', component: ManageListComponent},
          {path: 'projectCreate', component: ProjectCreateComponent},
          {path: 'projectEdit/:params', component: ProjectCreateComponent}
        ]
      },
      {
        path: 'detail/:params',
        component: DetailComponent,
        children: [
          {path: '', redirectTo: 'topology', pathMatch: 'full'},
          {path: 'topology', component:TopologyComponent},
          {path: 'compose', component:ComposeListComponent},
          {path: 'instance', component:InstanceComponent},
          {path: 'env/:params', component:EnvComponent},
          {path: 'container', component:ContainerListComponent},
          {path: 'container/:params', component:ContainerListComponent},
          {path: 'script', component:ScriptComponent},
          {path: 'scriptCreate', component:ScriptCreateComponent},
          {path: 'scriptEdit/:params', component:ScriptCreateComponent},
          {path: 'customerGroup', component:CustomerGroupComponent},
          {path: 'createCustomerGroup/:params', component:CreateCustomerGroupComponent},
          {path: 'task', component: TaskComponent},
          {path: 'taskCreate', component: TaskCreateComponent},
          {path: 'taskEdit/:params', component: TaskCreateComponent},
          {path: 'cronJob', component: CronJobsComponent},
          {path: 'cronJobCreate', component: CronJobsCreateComponent},
          {path: 'cronJobEdit/:params', component: CronJobsCreateComponent},
          {path: 'log', component: LogComponent},
          {path: 'logDetail/:params',component: LogDetailComponent}
        ]
      },

    ]
  },
  {
    path: 'webShell/:params',
    component: WebShellComponent
  },
  {
    path: 'login',
    component: LoginComponent
  }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
