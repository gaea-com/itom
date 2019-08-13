import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CopyGroupComponent } from './copy-group.component';

describe('CopyGroupComponent', () => {
  let component: CopyGroupComponent;
  let fixture: ComponentFixture<CopyGroupComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CopyGroupComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CopyGroupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
