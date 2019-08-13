import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PopUploadToDockerComponent } from './pop-upload-to-docker.component';

describe('PopUploadToDockerComponent', () => {
  let component: PopUploadToDockerComponent;
  let fixture: ComponentFixture<PopUploadToDockerComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PopUploadToDockerComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PopUploadToDockerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
