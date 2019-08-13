import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import {
  MatSidenavModule,
  MatInputModule,
  MatButtonModule,
  MatSnackBarModule,
  MatProgressBarModule,
  MatSelectModule,
  MatPaginatorModule,
  MatTableModule,
  MatDialogModule,
  MatRadioModule,
  MatSlideToggleModule,
  MatBottomSheetModule,
  MatCheckboxModule,
  MatTabsModule,
  MatExpansionModule,
  MatButtonToggleModule,
  MatMenuModule,
  MatTooltipModule,
  MatIconModule, MatBadgeModule, MatChipsModule, MatAutocompleteModule, MatStepperModule, MatListModule
} from '@angular/material';
import { BrowserAnimationsModule, NoopAnimationsModule} from "@angular/platform-browser/animations";
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { HomeComponent } from './home/home.component';
import { IndexComponent } from './index/index.component';
import { DetailComponent } from './detail/detail.component';
import { ManageListComponent } from './manage/manage-list/manage-list.component';
import { ProjectListComponent } from './project/project-list/project-list.component';
import { TopologyComponent } from './instance/topology/topology.component';
import {WebsocketService} from "./_Service/websocket.service";
import { WebShellComponent } from './shell/web-shell/web-shell.component';
import {ApiService} from "./_Service/api.service";
import {HTTP_INTERCEPTORS, HttpClientModule} from "@angular/common/http";
import {AuthServiceService} from "./_Service/auth-service.service";
import {AuthInterceptorService} from "./_Service/auth-interceptor.service";
import { LoginComponent } from './login/login.component';
import {FormsModule, ReactiveFormsModule} from "@angular/forms";
import {ToolsService} from "./_Service/tools.service";
import { ProjectCreateComponent } from './project/project-create/project-create.component';
import {CdkTableModule} from "@angular/cdk/table";
import { StatusPipe } from './_Pipe/status.pipe';
import { PopComponent } from './pop/pop.component';
import { AddGroupComponent } from './instance/add-group/add-group.component';
import {ChatService} from "./_Service/chat.service";
import { ImportComponent } from './instance/import/import.component';
import {OvserveFileService} from "./_Service/ovserve-file.service";
import { ComposeListComponent } from './compose/compose-list/compose-list.component';
import { AddComposeComponent } from './compose/add-compose/add-compose.component';
import { ConsoleComponent } from './console/console.component';
import {OvserveWSService} from "./_Service/ovserve-ws.service";
import { LinkInstanceComponent } from './instance/link-instance/link-instance.component';
import { CopyGroupComponent } from './instance/copy-group/copy-group.component';
import { InstanceComponent } from './instance/instance/instance.component';
import { EnvComponent } from './env/env.component';
import { TopologyTableComponent } from './instance/topology-table/topology-table.component';
import { DropMenuComponent } from './instance/drop-menu/drop-menu.component';
import {DragDropModule} from "@angular/cdk/drag-drop";
import {ObserveMessageService} from "./_Service/observe-message.service";
import { ContainerListComponent } from './container/container-list/container-list.component';
import {MessageCenterService} from "./_Service/message-center.service";
import { PopUploadToDockerComponent } from './container/pop-upload-to-docker/pop-upload-to-docker.component';
import { ScriptComponent } from './task/script/script.component';
import { ScriptCreateComponent } from './task/script-create/script-create.component';
import { PopRunScriptComponent } from './task/pop-run-script/pop-run-script.component';
import { CustomerGroupComponent } from './task/customer-group/customer-group.component';
import { CreateCustomerGroupComponent } from './task/create-customer-group/create-customer-group.component';
import { TaskComponent } from './task/task/task.component';
import { TaskCreateComponent } from './task/task-create/task-create.component';
import { TaskItemForCustomerGroupComponent } from './task/task-item-for-customer-group/task-item-for-customer-group.component';
import { SelectGroupComponent } from './task/select-group/select-group.component';
import { TaskItemComponent } from './task/task-item/task-item.component';
import { CronJobsComponent } from './task/cron-jobs/cron-jobs.component';
import { CronJobsCreateComponent } from './task/cron-jobs-create/cron-jobs-create.component';
import {OwlDateTimeModule, OwlNativeDateTimeModule} from "ng-pick-datetime";
import {CronJobsModule} from "ngx-cron-jobs";
import { SettingComponent } from './manage/setting/setting.component';
import { SendCmdToInstanceComponent } from './instance/send-cmd-to-instance/send-cmd-to-instance.component';
import { LogComponent } from './log/log/log.component';
import { LogDetailComponent } from './log/log-detail/log-detail.component';
import { PopLogDetailComponent } from './log/pop-log-detail/pop-log-detail.component';
import {InfiniteScrollModule} from "ngx-infinite-scroll";
import { SettingDetailComponent } from './manage/setting-detail/setting-detail.component';
import { AccountListComponent } from './manage/account-list/account-list.component';
import { PopCreateAccountComponent } from './manage/pop-create-account/pop-create-account.component';
import { ResetPasswordComponent } from './manage/reset-password/reset-password.component';
import { PopWebShellComponent } from './container/pop-web-shell/pop-web-shell.component';
import { PopPermComponent } from './manage/pop-perm/pop-perm.component';

@NgModule({
  exports: [
    CdkTableModule,
    MatSidenavModule,
    MatInputModule,
    MatSnackBarModule,
    MatButtonModule,
    MatProgressBarModule,
    MatSelectModule,
    MatTableModule,
    MatPaginatorModule,
    MatDialogModule,
    MatRadioModule,
    MatSlideToggleModule,
    MatBottomSheetModule,
    MatCheckboxModule,
    MatTabsModule,
    MatExpansionModule,
    MatButtonToggleModule,
    MatMenuModule,
    MatTooltipModule,
    DragDropModule,
    MatIconModule,
    MatChipsModule,
    MatBadgeModule,
    MatAutocompleteModule,
    MatStepperModule,
    MatListModule
  ]
})
export class ItomMaterialModule{}
@NgModule({
  declarations: [
    AppComponent,
    HomeComponent,
    IndexComponent,
    DetailComponent,
    SettingComponent,
    ManageListComponent,
    ProjectListComponent,
    ProjectCreateComponent,
    TopologyComponent,
    WebShellComponent,
    LoginComponent,
    StatusPipe,
    PopComponent,
    AddGroupComponent,
    ImportComponent,
    ComposeListComponent,
    AddComposeComponent,
    ConsoleComponent,
    LinkInstanceComponent,
    CopyGroupComponent,
    InstanceComponent,
    EnvComponent,
    TopologyTableComponent,
    DropMenuComponent,
    ContainerListComponent,
    PopUploadToDockerComponent,
    ScriptComponent,
    ScriptCreateComponent,
    PopRunScriptComponent,
    CustomerGroupComponent,
    CreateCustomerGroupComponent,
    TaskComponent,
    TaskCreateComponent,
    TaskItemForCustomerGroupComponent,
    SelectGroupComponent,
    TaskItemComponent,
    CronJobsComponent,
    CronJobsCreateComponent,
    SendCmdToInstanceComponent,
    LogComponent,
    LogDetailComponent,
    PopLogDetailComponent,
    SettingDetailComponent,
    AccountListComponent,
    PopCreateAccountComponent,
    ResetPasswordComponent,
    PopWebShellComponent,
    PopPermComponent
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    NoopAnimationsModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule,
    ItomMaterialModule,
    AppRoutingModule,
    OwlDateTimeModule,
    OwlNativeDateTimeModule,
    CronJobsModule,
    InfiniteScrollModule
  ],
  entryComponents: [
    PopComponent,
    AddGroupComponent,
    ImportComponent,
    ConsoleComponent,
    AddComposeComponent,
    LinkInstanceComponent,
    CopyGroupComponent,
    PopUploadToDockerComponent,
    PopRunScriptComponent,
    SendCmdToInstanceComponent,
    PopLogDetailComponent,
    PopCreateAccountComponent,
    ResetPasswordComponent,
    PopWebShellComponent,
    PopPermComponent
  ],
  providers: [
    ApiService,
    ToolsService,
    AuthServiceService,
    WebsocketService,
    ChatService,
    OvserveFileService,
    OvserveWSService,
    ObserveMessageService,
    MessageCenterService,
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptorService, multi: true },
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
