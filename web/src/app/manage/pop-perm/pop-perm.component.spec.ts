import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PopPermComponent } from './pop-perm.component';

describe('PopPermComponent', () => {
  let component: PopPermComponent;
  let fixture: ComponentFixture<PopPermComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PopPermComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PopPermComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
