import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AddComposeComponent } from './add-compose.component';

describe('AddComposeComponent', () => {
  let component: AddComposeComponent;
  let fixture: ComponentFixture<AddComposeComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AddComposeComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AddComposeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
