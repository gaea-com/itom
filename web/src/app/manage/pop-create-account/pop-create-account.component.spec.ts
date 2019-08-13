import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PopCreateAccountComponent } from './pop-create-account.component';

describe('PopCreateAccountComponent', () => {
  let component: PopCreateAccountComponent;
  let fixture: ComponentFixture<PopCreateAccountComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PopCreateAccountComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PopCreateAccountComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
