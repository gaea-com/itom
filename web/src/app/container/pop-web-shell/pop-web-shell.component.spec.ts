import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PopWebShellComponent } from './pop-web-shell.component';

describe('PopWebShellComponent', () => {
  let component: PopWebShellComponent;
  let fixture: ComponentFixture<PopWebShellComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PopWebShellComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PopWebShellComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
