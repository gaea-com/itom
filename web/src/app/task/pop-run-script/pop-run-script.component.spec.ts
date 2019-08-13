import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PopRunScriptComponent } from './pop-run-script.component';

describe('PopRunScriptComponent', () => {
  let component: PopRunScriptComponent;
  let fixture: ComponentFixture<PopRunScriptComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PopRunScriptComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PopRunScriptComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
