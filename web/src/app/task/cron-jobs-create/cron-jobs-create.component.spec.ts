import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CronJobsCreateComponent } from './cron-jobs-create.component';

describe('CronJobsCreateComponent', () => {
  let component: CronJobsCreateComponent;
  let fixture: ComponentFixture<CronJobsCreateComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CronJobsCreateComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CronJobsCreateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
