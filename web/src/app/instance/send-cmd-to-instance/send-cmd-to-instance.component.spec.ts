import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SendCmdToInstanceComponent } from './send-cmd-to-instance.component';

describe('SendCmdToInstanceComponent', () => {
  let component: SendCmdToInstanceComponent;
  let fixture: ComponentFixture<SendCmdToInstanceComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SendCmdToInstanceComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SendCmdToInstanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
