import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TaskItemForCustomerGroupComponent } from './task-item-for-customer-group.component';

describe('TaskItemForCustomerGroupComponent', () => {
  let component: TaskItemForCustomerGroupComponent;
  let fixture: ComponentFixture<TaskItemForCustomerGroupComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TaskItemForCustomerGroupComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TaskItemForCustomerGroupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
