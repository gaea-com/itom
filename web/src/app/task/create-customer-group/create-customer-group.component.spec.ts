import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CreateCustomerGroupComponent } from './create-customer-group.component';

describe('CreateCustomerGroupComponent', () => {
  let component: CreateCustomerGroupComponent;
  let fixture: ComponentFixture<CreateCustomerGroupComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CreateCustomerGroupComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CreateCustomerGroupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
