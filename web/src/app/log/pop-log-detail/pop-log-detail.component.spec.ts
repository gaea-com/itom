import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PopLogDetailComponent } from './pop-log-detail.component';

describe('PopLogDetailComponent', () => {
  let component: PopLogDetailComponent;
  let fixture: ComponentFixture<PopLogDetailComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PopLogDetailComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PopLogDetailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
