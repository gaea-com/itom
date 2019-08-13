import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ComposeListComponent } from './compose-list.component';

describe('ComposeListComponent', () => {
  let component: ComposeListComponent;
  let fixture: ComponentFixture<ComposeListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ComposeListComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ComposeListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
